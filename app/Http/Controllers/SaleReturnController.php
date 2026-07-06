<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Customer;
use App\Models\Drug;
use App\Models\UserDrugInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleReturnController extends Controller
{
    /* ═══════════════════════════════════════════════════
       INDEX — مع فلتر التاريخ من/إلى
       ═══════════════════════════════════════════════════ */
    public function index(Request $request)
    {
        $q         = $request->get('q', '');
        $period    = $request->get('period', 'all');
        $dateFrom  = $request->get('date_from', '');
        $dateTo    = $request->get('date_to', '');

        $query = SaleReturn::where('user_id', Auth::id())
            ->with(['sale', 'customer', 'items.drug'])
            ->latest();

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('return_number', 'like', "%$q%")
                   ->orWhereHas('sale', fn($s) => $s->where('invoice_number', 'like', "%$q%"))
                   ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$q%"));
            });
        }

        // فلتر التاريخ المخصص يتفوق على period
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [
                $dateFrom . ' 00:00:00',
                $dateTo   . ' 23:59:59',
            ]);
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
        $totalValue = SaleReturn::where('user_id', Auth::id())->sum('total');

        return view('sale_returns.index', compact('returns', 'q', 'period', 'totalValue', 'dateFrom', 'dateTo'));
    }

    /* ═══════════════════════════════════════════════════
       CREATE
       ═══════════════════════════════════════════════════ */
    public function create(Request $request)
    {
        $sale = null;
        if ($request->has('sale_id')) {
            $sale = Sale::where('user_id', Auth::id())
                ->with(['items.drug', 'customer'])
                ->findOrFail($request->sale_id);
        }

        $sales = Sale::where('user_id', Auth::id())
            ->with(['items.drug', 'customer'])
            ->latest()
            ->limit(50)
            ->get();

        return view('sale_returns.create', compact('sale', 'sales'));
    }

    /* ═══════════════════════════════════════════════════
       FETCH SALE — AJAX
       ✅ بيبعت available_units لكل صنف (علبة + شريط + حبة)
       ═══════════════════════════════════════════════════ */
    public function fetchSale(Request $request)
    {
        $sale = Sale::where('user_id', Auth::id())
            ->where(function ($q) use ($request) {
                $q->where('id', $request->sale_id)
                  ->orWhere('invoice_number', $request->sale_id);
            })
            ->with(['items.drug', 'customer'])
            ->first();

        if (!$sale) {
            return response()->json(['error' => 'الفاتورة غير موجودة'], 404);
        }

        $returnedQtys = SaleReturnItem::whereHas('saleReturn', fn($q) => $q->where('sale_id', $sale->id))
            ->selectRaw('sale_item_id, SUM(quantity) as returned')
            ->groupBy('sale_item_id')
            ->pluck('returned', 'sale_item_id');

        $items = $sale->items->map(function ($item) use ($returnedQtys) {
            $alreadyReturned = $returnedQtys[$item->id] ?? 0;
            // max_return بالوحدة الأساسية للفاتورة (نفس وحدة البيع)
            $maxReturn       = $item->quantity - $alreadyReturned;

            $drug     = $item->drug;
            $drugName = $drug?->name_ar ?? $drug?->name_en ?? $item->product_name ?? 'دواء';

            // ── بيانات الوحدات من جدول الدواء ──
            $majorUnits = max(1, (int) ($drug?->major_units ?? 1));   // عدد الشرايط في العلبة
            $minorUnits = max(1, (int) ($drug?->minor_units ?? 1));   // عدد الحبات في الشريط
            $stripName  = $drug?->strip_unit_name ?? 'شريط';
            $pieceName  = $drug?->piece_unit_name ?? 'حبة';

            // سعر البيع الفعلي بوحدة البيع الأصلية
            $unitPrice     = floatval($item->price);
            // qty_factor للصنف المباع (لو بيع بشريط مثلاً factor=0.333)
            $soldQtyFactor = floatval($item->qty_factor ?? 1);
            $soldUnitName  = $item->unit_name ?? 'علبة';
            $soldUnitKey   = $item->unit_key  ?? 'pack';

            // ── سعر العلبة الكاملة (لحساب باقي الوحدات) ──
            // لو بيع بعلبة: boxPrice = unitPrice
            // لو بيع بشريط (factor=1/major): boxPrice = unitPrice * major
            // لو بيع بحبة  (factor=1/(major*minor)): boxPrice = unitPrice * major * minor
            $boxPrice = round($unitPrice / $soldQtyFactor, 4);

            // ── بناء available_units ──
            // القاعدة: كل الوحدات تُبنى من boxPrice وتحتسب الـ max بالنسبة لـ maxReturn بالعلبة
            // maxReturn هنا = بوحدة البيع الأصلية → نحوّلها لعلبة أولاً
            $maxReturnInBoxes = $maxReturn * $soldQtyFactor; // الكمية المتبقية بالعلبة

            $availableUnits = [];

            // علبة — دايماً
            $maxInPack = (int) floor($maxReturnInBoxes);
            if ($maxInPack > 0) {
                $availableUnits[] = [
                    'key'        => 'pack',
                    'name'       => 'علبة',
                    'qty_factor' => 1,
                    'max'        => $maxInPack,
                    'price'      => round($boxPrice, 4),
                ];
            }

            // شريط (لو العلبة فيها أكثر من شريط)
            if ($majorUnits > 1) {
                $maxInStrip = (int) floor($maxReturnInBoxes * $majorUnits);
                if ($maxInStrip > 0) {
                    $availableUnits[] = [
                        'key'        => 'strip',
                        'name'       => $stripName,
                        'qty_factor' => round(1 / $majorUnits, 8),
                        'max'        => $maxInStrip,
                        'price'      => round($boxPrice / $majorUnits, 4),
                    ];
                }
            }

            // حبة (لو فيه حبات)
            if ($minorUnits > 1) {
                $totalPieces = $majorUnits * $minorUnits;
                $maxInPiece  = (int) floor($maxReturnInBoxes * $totalPieces);
                if ($maxInPiece > 0) {
                    $availableUnits[] = [
                        'key'        => 'piece',
                        'name'       => $pieceName,
                        'qty_factor' => round(1 / $totalPieces, 8),
                        'max'        => $maxInPiece,
                        'price'      => round($boxPrice / $totalPieces, 4),
                    ];
                }
            }

            // لو مفيش وحدات (كل الكمية اترجعت) — اعرض علبة بـ max=0
            if (empty($availableUnits)) {
                $availableUnits[] = [
                    'key'        => 'pack',
                    'name'       => 'علبة',
                    'qty_factor' => 1,
                    'max'        => 0,
                    'price'      => round($boxPrice, 4),
                ];
            }

            return [
                'id'               => $item->id,
                'drug_id'          => $item->drug_id,
                'product_name'     => $drugName,
                'category'         => $drug?->category,
                'sold_unit_name'   => $soldUnitName,
                'sold_unit_key'    => $soldUnitKey,
                'quantity'         => $item->quantity,
                'already_returned' => $alreadyReturned,
                'max_return'       => max(0, $maxReturn),
                'price'            => $unitPrice,
                'subtotal'         => $item->subtotal,
                'major_units'      => $majorUnits,
                'minor_units'      => $minorUnits,
                'strip_name'       => $stripName,
                'piece_name'       => $pieceName,
                'available_units'  => $availableUnits,
            ];
        });

        return response()->json([
            'sale' => [
                'id'             => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total'          => $sale->total,
                'created_at'     => $sale->created_at->format('Y-m-d'),
                'customer'       => $sale->customer?->name ?? 'عميل عام',
                'customer_id'    => $sale->customer_id,
            ],
            'items' => $items,
        ]);
    }

    /* ═══════════════════════════════════════════════════
       STORE
       ✅ بيقبل return_qty_factor مع كل صنف
       ═══════════════════════════════════════════════════ */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id'                           => 'required|integer|exists:sales,id',
            'refund_method'                     => 'required|in:cash,balance,none',
            'reason'                            => 'nullable|string|max:255',
            'notes'                             => 'nullable|string|max:1000',
            'items'                             => 'required|array|min:1',
            'items.*.sale_item_id'              => 'required|integer|exists:sale_items,id',
            'items.*.quantity'                  => 'required|integer|min:1',
            'items.*.return_unit_key'           => 'nullable|string',
            'items.*.return_unit_name'          => 'nullable|string',
            'items.*.return_qty_factor'         => 'nullable|numeric|min:0.0001',
            'items.*.return_unit_price'         => 'nullable|numeric|min:0',
        ], [
            'sale_id.required'       => 'يجب اختيار فاتورة بيع',
            'refund_method.required' => 'يجب اختيار طريقة الاسترداد',
            'items.required'         => 'يجب اختيار منتج واحد على الأقل',
            'items.*.quantity.min'   => 'الكمية يجب أن تكون 1 على الأقل',
        ]);

        try {
            $saleReturn = DB::transaction(function () use ($validated) {

                $userId = Auth::id();

                $sale = Sale::where('user_id', $userId)
                    ->with('items.drug')
                    ->findOrFail($validated['sale_id']);

                $total       = 0;
                $returnItems = [];

                foreach ($validated['items'] as $ri) {
                    $saleItem = $sale->items->firstWhere('id', $ri['sale_item_id']);
                    if (!$saleItem) {
                        throw new \Exception('صنف غير موجود في الفاتورة');
                    }

                    // ✅ الوحدة المختارة للإرجاع
                    $returnUnitKey   = $ri['return_unit_key']   ?? 'pack';
                    $returnUnitName  = $ri['return_unit_name']  ?? 'علبة';
                    $returnQtyFactor = floatval($ri['return_qty_factor'] ?? 1);
                    $returnUnitPrice = floatval($ri['return_unit_price'] ?? $saleItem->price);

                    // تحقق من الكمية المرتجعة مسبقاً (بوحدة البيع الأصلية)
                    $alreadyReturned = SaleReturnItem::whereHas(
                        'saleReturn', fn($q) => $q->where('sale_id', $sale->id)
                    )->where('sale_item_id', $saleItem->id)->sum('quantity');

                    // نحوّل الكمية المتبقية لعلبة للمقارنة
                    $soldQtyFactor     = floatval($saleItem->qty_factor ?? 1);
                    $maxInBoxes        = ($saleItem->quantity - $alreadyReturned) * $soldQtyFactor;
                    // الكمية المرتجعة الحالية بالعلبة
                    $returnedBoxes     = $ri['quantity'] * $returnQtyFactor;

                    if ($returnedBoxes > $maxInBoxes + 0.0001) {
                        $drugName = $saleItem->drug?->name_ar ?? $saleItem->drug?->name_en ?? 'دواء';
                        throw new \Exception(
                            'الكمية المرتجعة من "' . $drugName .
                            '" تتجاوز الحد المسموح.'
                        );
                    }

                    $subtotal = round($returnUnitPrice * $ri['quantity'], 2);
                    $total   += $subtotal;

                    $returnItems[] = [
                        'sale_item_id' => $saleItem->id,
                        'drug_id'      => $saleItem->drug_id,
                        'quantity'     => $ri['quantity'],
                        'price'        => $returnUnitPrice,
                        'subtotal'     => $subtotal,
                        // نحفظ qty_factor عشان الـ destroy يعكسه صح
                        'qty_factor'   => $returnQtyFactor,
                    ];

                    // ✅ زيادة المخزن بالكمية الفعلية بالعلبة
                    if ($saleItem->drug_id) {
                        $inv = UserDrugInventory::where('user_id', $userId)
                            ->currentBranch()
                            ->where('drug_id', $saleItem->drug_id)
                            ->lockForUpdate()
                            ->first();

                        if ($inv) {
                            $inv->increment('quantity', $returnedBoxes);
                            Log::info("✅ مرتجع بيع: +{$returnedBoxes} علبة لـ drug_id={$saleItem->drug_id} ({$ri['quantity']} {$returnUnitName})");
                        } else {
                            UserDrugInventory::create([
                                'user_id'   => $userId,
                                'branch_id' => \App\Support\Branch::id(),
                                'drug_id'   => $saleItem->drug_id,
                                'quantity'  => $returnedBoxes,
                            ]);
                        }
                    }
                }

                $saleReturn = SaleReturn::create([
                    'user_id'       => $userId,
                    'sale_id'       => $sale->id,
                    'customer_id'   => $sale->customer_id,
                    'return_number' => SaleReturn::generateNumber($userId),
                    'total'         => round($total, 2),
                    'refund_method' => $validated['refund_method'],
                    'reason'        => $validated['reason'] ?? null,
                    'notes'         => $validated['notes'] ?? null,
                ]);

                foreach ($returnItems as $ri) {
                    $ri['sale_return_id'] = $saleReturn->id;
                    SaleReturnItem::create($ri);
                }

                // تعديل رصيد العميل
                if ($sale->customer_id && $validated['refund_method'] === 'balance') {
                    $customer = Customer::find($sale->customer_id);
                    if ($customer && $customer->balance > 0) {
                        $customer->decrement('balance', min($total, $customer->balance));
                    }
                }

                return $saleReturn;
            });

            return redirect()
                ->route('sale-returns.show', $saleReturn)
                ->with('success', 'تم حفظ المرتجع بنجاح! رقم المرتجع: ' . $saleReturn->return_number);

        } catch (\Exception $e) {
            Log::error('SaleReturn error: ' . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /* ═══════════════════════════════════════════════════
       SHOW
       ═══════════════════════════════════════════════════ */
    public function show(SaleReturn $saleReturn)
    {
        abort_if($saleReturn->user_id !== Auth::id(), 403);
        $saleReturn->load(['sale', 'customer', 'items.drug', 'items.saleItem']);
        return view('sale_returns.show', compact('saleReturn'));
    }

    /* ═══════════════════════════════════════════════════
       DESTROY — عكس qty_factor الصح
       ═══════════════════════════════════════════════════ */
    public function destroy(SaleReturn $saleReturn)
    {
        abort_if($saleReturn->user_id !== Auth::id(), 403);

        try {
            DB::transaction(function () use ($saleReturn) {
                $userId = Auth::id();
                $saleReturn->load('items.saleItem');

                foreach ($saleReturn->items as $item) {
                    if (!$item->drug_id) continue;

                    // ✅ نستخدم qty_factor المحفوظ في الـ return item
                    $qtyFactor = floatval($item->qty_factor ?? $item->saleItem?->qty_factor ?? 1);
                    $boxesToRemove = $item->quantity * $qtyFactor;

                    $inv = UserDrugInventory::where('user_id', $userId)
                        ->currentBranch()
                        ->where('drug_id', $item->drug_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inv) {
                        $newQty = max(0, $inv->quantity - $boxesToRemove);
                        $inv->update(['quantity' => $newQty]);
                    }
                }

                // عكس رصيد العميل
                if ($saleReturn->customer_id && $saleReturn->refund_method === 'balance') {
                    Customer::find($saleReturn->customer_id)?->increment('balance', $saleReturn->total);
                }

                $saleReturn->delete();
            });

            return redirect()
                ->route('sale-returns.index')
                ->with('success', 'تم حذف المرتجع وعكس تأثيره على المخزن والرصيد');

        } catch (\Exception $e) {
            Log::error('SaleReturn delete error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'حدث خطأ أثناء الحذف']);
        }
    }

    /* ═══════════════════════════════════════════════════
       PRINT
       ═══════════════════════════════════════════════════ */
    public function print(SaleReturn $saleReturn)
    {
        abort_if($saleReturn->user_id !== Auth::id(), 403);
        $saleReturn->load(['sale', 'customer', 'items.drug']);
        return view('sale_returns.print', compact('saleReturn'));
    }
}