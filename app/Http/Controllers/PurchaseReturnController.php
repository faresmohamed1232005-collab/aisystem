<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\Drug;
use App\Models\UserDrugInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseReturnController extends Controller
{
    /* ═══════════════════════════════════════════════════
       INDEX
       ═══════════════════════════════════════════════════ */
    public function index(Request $request)
    {
        $q        = $request->get('q', '');
        $period   = $request->get('period', 'all');
        $dateFrom = $request->get('date_from', '');
        $dateTo   = $request->get('date_to', '');

        $query = PurchaseReturn::where('user_id', Auth::id())
            ->with(['purchaseInvoice', 'supplier', 'items'])
            ->latest();

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('return_number', 'like', "%$q%")
                   ->orWhereHas('purchaseInvoice', fn($s) => $s->where('invoice_number', 'like', "%$q%"))
                   ->orWhereHas('supplier', fn($c) => $c->where('name', 'like', "%$q%"));
            });
        }

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        } elseif ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        } else {
            match ($period) {
                'today' => $query->whereDate('created_at', today()),
                'week'  => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                default => null,
            };
        }

        $returns    = $query->paginate(20);
        $totalValue = PurchaseReturn::where('user_id', Auth::id())->sum('total');

        return view('purchase_returns.index', compact('returns', 'q', 'period', 'totalValue', 'dateFrom', 'dateTo'));
    }

    /* ═══════════════════════════════════════════════════
       CREATE
       ═══════════════════════════════════════════════════ */
    public function create(Request $request)
    {
        $invoice = null;
        if ($request->has('purchase_invoice_id')) {
            $invoice = PurchaseInvoice::where('user_id', Auth::id())
                ->with(['items', 'supplier'])
                ->findOrFail($request->purchase_invoice_id);
        }

        $invoices = PurchaseInvoice::where('user_id', Auth::id())
            ->with(['items', 'supplier'])
            ->latest()->limit(50)->get();

        return view('purchase_returns.create', compact('invoice', 'invoices'));
    }

    /* ═══════════════════════════════════════════════════
       FETCH INVOICE (AJAX)
       ═══════════════════════════════════════════════════ */
    public function fetchInvoice(Request $request)
    {
        $invoice = PurchaseInvoice::where('user_id', Auth::id())
            ->where(function ($q) use ($request) {
                $q->where('id', $request->invoice_id)
                  ->orWhere('invoice_number', $request->invoice_id);
            })
            ->with(['items', 'supplier'])
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'الفاتورة غير موجودة'], 404);
        }

        $returnedQtys = PurchaseReturnItem::whereHas(
            'purchaseReturn',
            fn($q) => $q->where('purchase_invoice_id', $invoice->id)
        )
        ->selectRaw('purchase_invoice_item_id, SUM(quantity) as returned')
        ->groupBy('purchase_invoice_item_id')
        ->pluck('returned', 'purchase_invoice_item_id');

        $items = $invoice->items->map(function ($item) use ($returnedQtys) {
            $alreadyReturned = $returnedQtys[$item->id] ?? 0;
            $maxReturn       = $item->quantity - $alreadyReturned;

            $drug = null;
            if ($item->drug_id)                   $drug = Drug::find($item->drug_id);
            if (!$drug && !empty($item->barcode))  $drug = Drug::where('barcode', $item->barcode)->first();
            if (!$drug) {
                $drug = Drug::where('name_ar', $item->product_name)
                            ->orWhere('name_en', $item->product_name)->first();
            }

            $majorUnits = max(1, (int) ($drug?->major_units ?? 1));
            $minorUnits = max(1, (int) ($drug?->minor_units ?? 1));
            $stripName  = $drug?->strip_unit_name ?? 'شريط';
            $pieceName  = $drug?->piece_unit_name ?? 'حبة';
            $boxPrice   = floatval($item->purchase_price);

            $availableUnits = [];
            $maxInPack = (int) floor($maxReturn);
            if ($maxInPack > 0) {
                $availableUnits[] = ['key' => 'pack', 'name' => 'علبة', 'qty_factor' => 1, 'max' => $maxInPack, 'price' => $boxPrice];
            }
            if ($majorUnits > 1) {
                $maxInStrip = (int) floor($maxReturn * $majorUnits);
                if ($maxInStrip > 0) {
                    $availableUnits[] = ['key' => 'strip', 'name' => $stripName, 'qty_factor' => round(1 / $majorUnits, 8), 'max' => $maxInStrip, 'price' => round($boxPrice / $majorUnits, 4)];
                }
            }
            if ($minorUnits > 1) {
                $totalPieces = $majorUnits * $minorUnits;
                $maxInPiece  = (int) floor($maxReturn * $totalPieces);
                if ($maxInPiece > 0) {
                    $availableUnits[] = ['key' => 'piece', 'name' => $pieceName, 'qty_factor' => round(1 / $totalPieces, 8), 'max' => $maxInPiece, 'price' => round($boxPrice / $totalPieces, 4)];
                }
            }
            if (empty($availableUnits)) {
                $availableUnits[] = ['key' => 'pack', 'name' => 'علبة', 'qty_factor' => 1, 'max' => 0, 'price' => $boxPrice];
            }

            return [
                'id'               => $item->id,
                'drug_id'          => $item->drug_id ?? $drug?->id,
                'product_name'     => $item->product_name,
                'category'         => $item->category,
                'quantity'         => $item->quantity,
                'already_returned' => $alreadyReturned,
                'max_return'       => max(0, $maxReturn),
                'purchase_price'   => $boxPrice,
                'available_units'  => $availableUnits,
                'major_units'      => $majorUnits,
                'minor_units'      => $minorUnits,
                'strip_name'       => $stripName,
                'piece_name'       => $pieceName,
            ];
        });

        return response()->json([
            'invoice' => [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'net_total'      => $invoice->net_total,
                'invoice_date'   => $invoice->invoice_date ?? $invoice->created_at->format('Y-m-d'),
                'supplier'       => $invoice->supplier?->name ?? 'بدون مورد',
                'supplier_id'    => $invoice->supplier_id,
            ],
            'items' => $items,
        ]);
    }

    /* ═══════════════════════════════════════════════════
       STORE
       ═══════════════════════════════════════════════════ */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_invoice_id'               => 'required|integer|exists:purchase_invoices,id',
            'refund_method'                     => 'required|in:cash,balance,none',
            'reason'                            => 'nullable|string|max:255',
            'notes'                             => 'nullable|string|max:1000',
            'items'                             => 'required|array|min:1',
            'items.*.invoice_item_id'           => 'required|integer|exists:purchase_invoice_items,id',
            'items.*.quantity'                  => 'required|integer|min:1',
            'items.*.return_unit_key'           => 'nullable|string',
            'items.*.return_unit_name'          => 'nullable|string',
            'items.*.return_qty_factor'         => 'nullable|numeric|min:0.0001',
            'items.*.return_unit_price'         => 'nullable|numeric|min:0',
        ], [
            'purchase_invoice_id.required' => 'يجب اختيار فاتورة شراء',
            'refund_method.required'       => 'يجب اختيار طريقة الاسترداد',
            'items.required'               => 'يجب اختيار منتج واحد على الأقل',
        ]);

        try {
            $purchaseReturn = DB::transaction(function () use ($validated) {

                $userId  = Auth::id();
                $invoice = PurchaseInvoice::where('user_id', $userId)
                    ->with('items')
                    ->findOrFail($validated['purchase_invoice_id']);

                $total       = 0;
                $returnItems = [];

                /* ── بناء بنود المرتجع وتحديث المخزن ── */
                foreach ($validated['items'] as $ri) {
                    $invItem = $invoice->items->firstWhere('id', $ri['invoice_item_id']);
                    if (!$invItem) throw new \Exception('صنف غير موجود في الفاتورة');

                    $returnUnitKey   = $ri['return_unit_key']   ?? 'pack';
                    $returnUnitName  = $ri['return_unit_name']  ?? 'علبة';
                    $returnQtyFactor = floatval($ri['return_qty_factor'] ?? 1);
                    $returnUnitPrice = floatval($ri['return_unit_price'] ?? $invItem->purchase_price);

                    $alreadyReturned = PurchaseReturnItem::whereHas(
                        'purchaseReturn',
                        fn($q) => $q->where('purchase_invoice_id', $invoice->id)
                    )->where('purchase_invoice_item_id', $invItem->id)->sum('quantity');

                    $maxReturnBoxes = ($invItem->quantity - $alreadyReturned);
                    $returnedBoxes  = $ri['quantity'] * $returnQtyFactor;

                    if ($returnedBoxes > $maxReturnBoxes + 0.0001) {
                        throw new \Exception('الكمية المرتجعة من "' . $invItem->product_name . '" تتجاوز الحد المسموح.');
                    }

                    $subtotal = round($returnUnitPrice * $ri['quantity'], 2);
                    $total   += $subtotal;

                    $drugId = $invItem->drug_id ?? null;
                    if (!$drugId) {
                        $drug = null;
                        if (!empty($invItem->barcode)) $drug = Drug::where('barcode', $invItem->barcode)->first();
                        if (!$drug) {
                            $drug = Drug::where('name_ar', $invItem->product_name)
                                        ->orWhere('name_en', $invItem->product_name)->first();
                        }
                        $drugId = $drug?->id;
                        if ($drugId) $invItem->update(['drug_id' => $drugId]);
                    }

                    $returnItems[] = [
                        'purchase_invoice_item_id' => $invItem->id,
                        'drug_id'                  => $drugId,
                        'product_name'             => $invItem->product_name,
                        'quantity'                 => $ri['quantity'],
                        'purchase_price'           => $returnUnitPrice,
                        'subtotal'                 => $subtotal,
                    ];

                    if ($drugId) {
                        $inv = UserDrugInventory::where('user_id', $userId)->where('drug_id', $drugId)->lockForUpdate()->first();
                        if ($inv) {
                            $inv->update(['quantity' => max(0, $inv->quantity - $returnedBoxes)]);
                            Log::info("✅ مرتجع شراء: -{$returnedBoxes} من drug_id={$drugId} ({$ri['quantity']} {$returnUnitName})");
                        } else {
                            Log::warning("⚠️ مفيش مخزون لـ drug_id={$drugId}");
                        }
                    }
                }

                $total = round($total, 2);

                /* ── إنشاء سجل المرتجع ── */
                $purchaseReturn = PurchaseReturn::create([
                    'user_id'             => $userId,
                    'purchase_invoice_id' => $invoice->id,
                    'supplier_id'         => $invoice->supplier_id,
                    'return_number'       => PurchaseReturn::generateNumber(),
                    'total'               => $total,
                    'refund_method'       => $validated['refund_method'],
                    'reason'             => $validated['reason'] ?? null,
                    'notes'              => $validated['notes']  ?? null,
                ]);

                foreach ($returnItems as $ri) {
                    $ri['purchase_return_id'] = $purchaseReturn->id;
                    PurchaseReturnItem::create($ri);
                }

                /* ══════════════════════════════════════════════════════
                 *  خوارزمية توزيع رصيد المرتجع (refund_method = balance)
                 *
                 *  1️⃣  الفاتورة الأصلية المرتجَعة منها (لو فيها متبقي)
                 *  2️⃣  باقي فواتير المورد الغير مسددة (الأقدم أولاً)
                 *  3️⃣  اللي يفضل → رصيد لصالحك (balance سالب عند المورد)
                 * ══════════════════════════════════════════════════════ */
                if ($invoice->supplier_id && $validated['refund_method'] === 'balance') {

                    $supplier   = Supplier::lockForUpdate()->find($invoice->supplier_id);
                    $creditLeft = $total;

                    /* 1️⃣ الفاتورة الأصلية */
                    $invoice->refresh();
                    if ($invoice->remaining > 0.001) {
                        $apply        = min($creditLeft, $invoice->remaining);
                        $newRemaining = round($invoice->remaining - $apply, 2);
                        $invoice->update([
                            'paid'           => round($invoice->paid + $apply, 2),
                            'remaining'      => $newRemaining,
                            'payment_status' => $newRemaining <= 0.001 ? 'paid' : 'partial',
                        ]);
                        $creditLeft = round($creditLeft - $apply, 2);
                        Log::info("💳 [1] سُدِّد {$apply} ج.م من فاتورة #{$invoice->invoice_number} — باقي: {$newRemaining}");
                    }

                    /* 2️⃣ باقي فواتير المورد الغير مسددة (الأقدم تاريخاً أولاً) */
                    if ($creditLeft > 0.001) {
                        $otherInvoices = PurchaseInvoice::where('user_id', $userId)
                            ->where('supplier_id', $invoice->supplier_id)
                            ->where('id', '!=', $invoice->id)
                            ->where('remaining', '>', 0)
                            ->oldest('invoice_date')
                            ->lockForUpdate()
                            ->get();

                        foreach ($otherInvoices as $other) {
                            if ($creditLeft <= 0.001) break;
                            $apply        = min($creditLeft, $other->remaining);
                            $newRemaining = round($other->remaining - $apply, 2);
                            $other->update([
                                'paid'           => round($other->paid + $apply, 2),
                                'remaining'      => $newRemaining,
                                'payment_status' => $newRemaining <= 0.001 ? 'paid' : 'partial',
                            ]);
                            $creditLeft = round($creditLeft - $apply, 2);
                            Log::info("💳 [2] سُدِّد {$apply} ج.م من فاتورة #{$other->invoice_number} — باقي: {$newRemaining}");
                        }
                    }

                    /* 3️⃣ تحديث رصيد المورد
                     *  لو creditLeft > 0 → balance هيبقى سالب = المورد مديون ليك
                     */
                    if ($supplier) {
                        $newBalance = round($supplier->balance - $total, 2);
                        $supplier->update(['balance' => $newBalance]);
                        if ($creditLeft > 0.001) {
                            Log::info("🏦 رصيد لصالحك عند المورد: {$creditLeft} ج.م — رصيد المورد: {$newBalance}");
                        }
                    }
                }

                return $purchaseReturn;
            });

            return redirect()
                ->route('purchase-returns.show', $purchaseReturn)
                ->with('success', 'تم حفظ المرتجع بنجاح! رقم المرتجع: ' . $purchaseReturn->return_number);

        } catch (\Exception $e) {
            Log::error('PurchaseReturn error: ' . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /* ═══════════════════════════════════════════════════
       SHOW
       ═══════════════════════════════════════════════════ */
    public function show(PurchaseReturn $purchaseReturn)
    {
        abort_if($purchaseReturn->user_id !== Auth::id(), 403);
        $purchaseReturn->load(['purchaseInvoice', 'supplier', 'items']);
        return view('purchase_returns.show', compact('purchaseReturn'));
    }

    /* ═══════════════════════════════════════════════════
       DESTROY — عكس كل التأثيرات بالترتيب العكسي
       ═══════════════════════════════════════════════════ */
    public function destroy(PurchaseReturn $purchaseReturn)
    {
        abort_if($purchaseReturn->user_id !== Auth::id(), 403);

        try {
            DB::transaction(function () use ($purchaseReturn) {
                $userId = Auth::id();
                $purchaseReturn->load('items');

                /* ── إرجاع المخزن ── */
                foreach ($purchaseReturn->items as $item) {
                    if (!$item->drug_id) continue;
                    $inv = UserDrugInventory::where('user_id', $userId)
                        ->where('drug_id', $item->drug_id)->lockForUpdate()->first();
                    if ($inv) $inv->increment('quantity', $item->quantity);
                }

                /* ── عكس التأثير على الفواتير + رصيد المورد ── */
                if ($purchaseReturn->refund_method === 'balance' && $purchaseReturn->supplier_id) {

                    $totalToReverse = round($purchaseReturn->total, 2);
                    $reverseLeft    = $totalToReverse;

                    // عكس الترتيب: الأحدث تاريخاً أولاً (عكس ما سددناه)
                    $otherInvoices = PurchaseInvoice::where('user_id', $userId)
                        ->where('supplier_id', $purchaseReturn->supplier_id)
                        ->where('id', '!=', $purchaseReturn->purchase_invoice_id)
                        ->where('paid', '>', 0)
                        ->latest('invoice_date')
                        ->lockForUpdate()
                        ->get();

                    foreach ($otherInvoices as $other) {
                        if ($reverseLeft <= 0.001) break;
                        $reverse      = min($reverseLeft, $other->paid);
                        $newPaid      = round($other->paid - $reverse, 2);
                        $newRemaining = round($other->remaining + $reverse, 2);
                        $other->update([
                            'paid'           => $newPaid,
                            'remaining'      => $newRemaining,
                            'payment_status' => $newPaid <= 0.001 ? 'unpaid' : 'partial',
                        ]);
                        $reverseLeft = round($reverseLeft - $reverse, 2);
                        Log::info("↩️ عُكس {$reverse} ج.م على فاتورة #{$other->invoice_number}");
                    }

                    // ثم الفاتورة الأصلية
                    if ($reverseLeft > 0.001 && $purchaseReturn->purchase_invoice_id) {
                        $origInvoice = PurchaseInvoice::lockForUpdate()->find($purchaseReturn->purchase_invoice_id);
                        if ($origInvoice) {
                            $reverse      = min($reverseLeft, $origInvoice->paid);
                            $newPaid      = round($origInvoice->paid - $reverse, 2);
                            $newRemaining = round($origInvoice->remaining + $reverse, 2);
                            $origInvoice->update([
                                'paid'           => $newPaid,
                                'remaining'      => $newRemaining,
                                'payment_status' => $newPaid <= 0.001 ? 'unpaid' : 'partial',
                            ]);
                            Log::info("↩️ عُكس {$reverse} ج.م على الفاتورة الأصلية #{$origInvoice->invoice_number}");
                        }
                    }

                    // إعادة رصيد المورد
                    $supplier = Supplier::lockForUpdate()->find($purchaseReturn->supplier_id);
                    if ($supplier) {
                        $supplier->update(['balance' => round($supplier->balance + $totalToReverse, 2)]);
                    }
                }

                $purchaseReturn->delete();
            });

            return redirect()->route('purchase-returns.index')
                ->with('success', 'تم حذف المرتجع وعكس تأثيره على المخزن والفواتير ورصيد المورد');

        } catch (\Exception $e) {
            Log::error('PurchaseReturn delete error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'حدث خطأ أثناء الحذف: ' . $e->getMessage()]);
        }
    }

    /* ═══════════════════════════════════════════════════
       PRINT
       ═══════════════════════════════════════════════════ */
    public function print(PurchaseReturn $purchaseReturn)
    {
        abort_if($purchaseReturn->user_id !== Auth::id(), 403);
        $purchaseReturn->load(['purchaseInvoice', 'supplier', 'items']);
        return view('purchase_returns.print', compact('purchaseReturn'));
    }
}