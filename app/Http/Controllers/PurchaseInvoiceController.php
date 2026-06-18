<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Drug;
use App\Models\UserDrugInventory;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceController extends Controller
{
    /* ========================= INDEX ========================= */
    public function index(Request $request)
    {
        $q      = $request->get('q', '');
        $period = $request->get('period', 'all');

        $query = PurchaseInvoice::where('user_id', Auth::id())
            ->with(['supplier', 'items'])
            ->latest();

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('invoice_number', 'like', "%$q%")
                   ->orWhereHas('supplier', function ($s) use ($q) {
                       $s->where('name', 'like', "%$q%");
                   });
            });
        }

        if ($period === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($period === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        $purchases = $query->paginate(20);

        $totalSpent = PurchaseInvoice::where('user_id', Auth::id())->sum('net_total');

        $totalDeferred = PurchaseInvoice::where('user_id', Auth::id())
            ->where('payment_status', '!=', 'paid')
            ->sum('remaining');

        return view('purchases.index', compact(
            'purchases',
            'q',
            'period',
            'totalSpent',
            'totalDeferred'
        ));
    }

    /* ========================= CREATE ========================= */
    public function create()
    {
        return view('purchases.create');
    }

    /* ========================= STORE ========================= */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'            => 'nullable|integer|exists:suppliers,id',
            'invoice_number'         => 'required|string|max:100',
            'invoice_date'           => 'nullable|date',
            'payment_method'         => 'required|in:cash,card,transfer,deferred',
            'paid'                   => 'required|numeric|min:0',
            'notes'                  => 'nullable|string',
            'invoice_discount'       => 'nullable|numeric|min:0',
            'invoice_extra'          => 'nullable|numeric|min:0',
            'items'                  => 'required|array|min:1',
            'items.*.product_name'   => 'required|string|max:255',
            'items.*.category'       => 'nullable|string',
            'items.*.barcode'        => 'nullable|string|max:100',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.selling_price'  => 'required|numeric|min:0',
            'items.*.quantity'       => 'required|numeric|min:0.0001',
            'items.*.expiry_date'    => 'nullable|date',
            'items.*.major_units'    => 'nullable|integer|min:1',
            'items.*.minor_units'    => 'nullable|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($validated, &$invoice) {

                $subtotalItems = 0;
                $invoiceItems  = [];

                foreach ($validated['items'] as $item) {

                    $subtotal       = $item['purchase_price'] * $item['quantity'];
                    $subtotalItems += $subtotal;

                    $invoiceItems[] = [
                        'product_name'   => $item['product_name'],
                        'category'       => $item['category'] ?? null,
                        'barcode'        => $item['barcode'] ?? null,
                        'purchase_price' => $item['purchase_price'],
                        'selling_price'  => $item['selling_price'],
                        'quantity'       => $item['quantity'],
                        'expiry_date'    => $item['expiry_date'] ?? null,
                        'subtotal'       => round($subtotal, 2),
                        'major_units'    => intval($item['major_units'] ?? 1),
                        'minor_units'    => intval($item['minor_units'] ?? 1),
                    ];
                }

                $invoiceDiscount = floatval($validated['invoice_discount'] ?? 0);
                $invoiceExtra    = floatval($validated['invoice_extra'] ?? 0);
                $netTotal        = round($subtotalItems - $invoiceDiscount + $invoiceExtra, 2);

                $paid      = floatval($validated['paid']);
                $remaining = max(0, $netTotal - $paid);
                $payStatus = $remaining > 0 ? 'partial' : 'paid';

                /* ===== حفظ الفاتورة ===== */
                $invoice = PurchaseInvoice::create([
                    'user_id'        => Auth::id(),
                    'supplier_id'    => $validated['supplier_id'] ?? null,
                    'invoice_number' => $validated['invoice_number'],
                    'invoice_date'   => $validated['invoice_date'] ?? now(),
                    'total'          => round($subtotalItems, 2),
                    'discount'       => $invoiceDiscount,
                    'extra'          => $invoiceExtra,
                    'net_total'      => $netTotal,
                    'paid'           => $paid,
                    'remaining'      => $remaining,
                    'payment_status' => $payStatus,
                    'payment_method' => $validated['payment_method'],
                    'notes'          => $validated['notes'] ?? null,
                ]);

                /* ===== تحديث المخزون ===== */
                foreach ($invoiceItems as $item) {

                    $drug = null;

                    // ① البحث بالباركود أولاً
                    if (!empty($item['barcode'])) {
                        $drug = Drug::where('barcode', $item['barcode'])->first();
                    }

                    // ② البحث بالاسم لو ما لقاش بالباركود
                    if (!$drug) {
                        $drug = Drug::where('name_ar', $item['product_name'])
                            ->orWhere('name_en', $item['product_name'])
                            ->first();
                    }

                    if (!$drug) {
                        // ③ منتج جديد خالص → أنشئه
                        $drug = Drug::create([
                            'name_ar'     => $item['product_name'],
                            'name_en'     => $item['product_name'],
                            'barcode'     => $item['barcode'] ?? null,
                            'category'    => $item['category'] ?? null,
                            'new_price'   => $item['selling_price'],
                            'old_price'   => $item['selling_price'],
                            'major_units' => $item['major_units'],
                            'minor_units' => $item['minor_units'],
                            'popularity'  => 0,
                        ]);
                    } else {
                        // ④ منتج موجود → حدّث بياناته
                        $needsSave = false;

                        if (!empty($item['category']) && $drug->category !== $item['category']) {
                            $drug->category = $item['category'];
                            $needsSave = true;
                        }

                        if (!empty($item['barcode']) && empty($drug->barcode)) {
                            $drug->barcode = $item['barcode'];
                            $needsSave = true;
                        }

                        if ($item['major_units'] > 1 && $drug->major_units !== $item['major_units']) {
                            $drug->major_units = $item['major_units'];
                            $needsSave = true;
                        }

                        if ($item['minor_units'] > 1 && $drug->minor_units !== $item['minor_units']) {
                            $drug->minor_units = $item['minor_units'];
                            $needsSave = true;
                        }

                        if ($needsSave) {
                            $drug->save();
                        }
                    }

                    // ⑤ تحديث المخزون — كل باتش (دواء + تاريخ صلاحية) سجل مستقل
                    $expiryDate = $item['expiry_date'] ?? null;

                    $inventory = UserDrugInventory::firstOrNew([
                        'user_id'     => Auth::id(),
                        'drug_id'     => $drug->id,
                        'expiry_date' => $expiryDate,
                    ]);

                    $inventory->quantity     = ($inventory->quantity ?? 0) + $item['quantity'];
                    $inventory->cost_price   = $item['purchase_price'];
                    $inventory->custom_price = $item['selling_price'];
                    $inventory->save();

                    /* ===== حفظ عنصر الفاتورة ===== */
                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id' => $invoice->id,
                        'product_id'          => null,
                        'product_name'        => $item['product_name'],
                        'quantity'            => $item['quantity'],
                        'purchase_price'      => $item['purchase_price'],
                        'selling_price'       => $item['selling_price'],
                        'subtotal'            => $item['subtotal'],
                    ]);
                }

                /* ===== تحديث رصيد المورد لو فيه متبقي ===== */
                if ($remaining > 0 && !empty($validated['supplier_id'])) {
                    Supplier::where('id', $validated['supplier_id'])
                        ->increment('balance', $remaining);
                }
            });

            return response()->json([
                'success'  => true,
                'message'  => 'تم حفظ الفاتورة وتحديث المخزن ✅',
                'redirect' => route('purchases.index'),
            ]);

        } catch (\Exception $e) {

            Log::error('Purchase Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ========================= SHOW ========================= */
    public function show(PurchaseInvoice $purchaseInvoice)
    {
        abort_if($purchaseInvoice->user_id !== Auth::id(), 403);

        $purchaseInvoice->load('supplier', 'items');

        return view('purchases.show', compact('purchaseInvoice'));
    }

    /* ========================= PRINT ========================= */
    public function printInvoice(PurchaseInvoice $purchaseInvoice)
    {
        abort_if($purchaseInvoice->user_id !== Auth::id(), 403);

        $purchaseInvoice->load('supplier', 'items');

        $invoice = $purchaseInvoice;

        return view('purchases.print', compact('invoice'));
    }
}