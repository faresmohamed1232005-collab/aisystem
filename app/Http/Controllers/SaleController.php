<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\UserDrugInventory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Support\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::where('user_id', Auth::id())
            ->with(['items', 'customer'])
            ->latest()
            ->paginate(20);

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        return view('sales.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.id'         => 'required|integer|exists:drugs,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.qty_factor' => 'nullable|numeric|min:0.000001',
            'items.*.unit_key'   => 'nullable|string',
            'items.*.unit_name'  => 'nullable|string',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'payment_method'     => 'required|in:cash,card,insurance,deferred',
            'card_type'          => 'nullable|in:visa,instapay,wallet',
            'customer_id'        => 'nullable|integer|exists:customers,id',
            'contract_id'        => 'nullable|integer|exists:contracts,id',
            'insured_patient_id' => 'nullable|integer|exists:insured_patients,id',
            'discount'           => 'nullable|numeric|min:0',
            'paid'               => 'required|numeric|min:0',
            'notes'              => 'nullable|string|max:500',
            'delivery_type'      => 'nullable|in:store,delivery',
            'delivery_address'   => 'nullable|string|max:500',
            'delivery_phone'     => 'nullable|string|max:20',
        ]);

        try {
            $sale = DB::transaction(function () use ($validated) {

                $total     = 0;
                $saleItems = [];
                $userId    = Auth::id();

                foreach ($validated['items'] as $item) {

                    $drug = Drug::lockForUpdate()->findOrFail($item['id']);

                    // ══════════════════════════════════════════════════════
                    // ① جيب كل الباتشات مرتبة من أقرب صلاحية للأبعد (FIFO)
                    //    الباتشات اللي مفيهاش تاريخ تيجي آخر
                    // ══════════════════════════════════════════════════════
                    $batches = UserDrugInventory::where('user_id', $userId)
                        ->where('drug_id', $drug->id)
                        ->where('quantity', '>', 0)
                        ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('expiry_date', 'asc')
                        ->lockForUpdate()
                        ->get();

                    // ② إجمالي المتاح عبر كل الباتشات
                    $totalAvailable = $batches->sum('quantity');

                    $unitKey   = $item['unit_key']  ?? 'pack';
                    $unitName  = $item['unit_name']  ?? 'علبة';

                    // سعر البيع: من الطلب أو من أول باتش أو من الدواء
                    $firstBatch = $batches->first();
                    $unitPrice  = floatval(
                        $item['unit_price']
                        ?? $firstBatch?->custom_price
                        ?? $drug->new_price
                        ?? $drug->old_price
                        ?? 0
                    );

                    $qtyFactor   = floatval($item['qty_factor'] ?? 1);
                    $qtyConsumed = $item['qty'] * $qtyFactor;   // الكمية بوحدة العلبة

                    // ③ تحقق من توفر الكمية الإجمالية
                    if ($totalAvailable < $qtyConsumed) {
                        $drugName  = $drug->name_ar ?? $drug->name_en;
                        $majorUnits = max(1, (int) $drug->major_units);
                        $stripName  = $firstBatch?->strip_unit_name ?? 'شريط';
                        $msg = "المتاح: " . ProductsController::formatBoxStrip(
                            $totalAvailable, $majorUnits, $stripName
                        );
                        throw new \Exception(
                            "الكمية المطلوبة من \"{$drugName}\" ({$item['qty']} {$unitName}) غير متوفرة. {$msg}"
                        );
                    }

                    // ④ اخصم من الباتشات بالترتيب (FIFO) — أقرب صلاحية الأول
                    $remaining = $qtyConsumed;
                    $costPrice = 0;

                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;

                        $deduct    = min($batch->quantity, $remaining);
                        $costPrice = floatval($batch->cost_price ?? 0);   // آخر سعر شراء من الباتش المخصوم

                        $batch->quantity -= $deduct;
                        $batch->save();

                        $remaining -= $deduct;
                    }

                    $subtotal  = $unitPrice * $item['qty'];
                    $total    += $subtotal;

                    $saleItems[] = [
                        'drug_id'    => $drug->id,
                        'quantity'   => $item['qty'],
                        'unit_key'   => $unitKey,
                        'unit_name'  => $unitName,
                        'unit_price' => $unitPrice,
                        'qty_factor' => $qtyFactor,
                        'price'      => $unitPrice,
                        'subtotal'   => round($subtotal, 2),
                        'cost_price' => $costPrice,
                    ];
                }

                $discount  = floatval($validated['discount'] ?? 0);
                $netTotal  = max(0, $total - $discount);
                $paid      = floatval($validated['paid']);
                $remaining = max(0, $netTotal - $paid);

                // ══════════════════════════════════════════════
                // ✅ التحقق من حد الآجل قبل الحفظ
                // ══════════════════════════════════════════════
                if ($remaining > 0 && !empty($validated['customer_id'])) {
                    $customer = Customer::lockForUpdate()->find($validated['customer_id']);
                    if ($customer && $customer->credit_limit > 0) {
                        $newBalance = $customer->balance + $remaining;
                        if ($newBalance > $customer->credit_limit) {
                            $available = max(0, $customer->credit_limit - $customer->balance);
                            throw new \Exception(
                                "تجاوز حد الآجل للعميل \"{$customer->name}\"!\n" .
                                "الحد المسموح: " . number_format($customer->credit_limit, 2) . " ج.م\n" .
                                "الرصيد الحالي: " . number_format($customer->balance, 2) . " ج.م\n" .
                                "المبلغ الآجل الجديد: " . number_format($remaining, 2) . " ج.م\n" .
                                "المتاح للآجل: " . number_format($available, 2) . " ج.م"
                            );
                        }
                    }
                }

                $paymentStatus = 'paid';
                if ($validated['payment_method'] === 'deferred') {
                    $paymentStatus = $paid > 0 ? 'partial' : 'deferred';
                } elseif ($remaining > 0) {
                    $paymentStatus = 'partial';
                }

                // ══════════════════════════════════════════════
                // Contract Pricing Engine — تقسيم فاتورة التأمين تلقائياً
                // ══════════════════════════════════════════════
                $coveredAmount = 0;
                $patientAmount = $netTotal;
                $contractId    = null;
                if ($validated['payment_method'] === 'insurance' && !empty($validated['contract_id'])) {
                    $contract = \App\Models\Contract::with('insuranceRule')
                        ->where('user_id', Auth::id())
                        ->find($validated['contract_id']);
                    if ($contract) {
                        $contractId = $contract->id;
                        $split = app(\App\Services\Insurance\ContractPricingEngine::class)->split($contract, $netTotal);
                        $coveredAmount = $split['covered'];
                        $patientAmount = $split['patient'];
                    }
                }

                $sale = Sale::create([
                    'user_id'            => Auth::id(),
                    'customer_id'        => $validated['customer_id'] ?? null,
                    'contract_id'        => $contractId,
                    'insured_patient_id' => $contractId ? ($validated['insured_patient_id'] ?? null) : null,
                    'covered_amount'     => $coveredAmount,
                    'patient_amount'     => $patientAmount,
                    'invoice_number'   => Branch::code() . '-INV-' . strtoupper(substr(uniqid(), -8)),
                    'total'            => $netTotal,
                    'discount'         => $discount,
                    'paid'             => $paid,
                    'remaining'        => $remaining,
                    'payment_method'   => $validated['payment_method'],
                    'card_type'        => $validated['card_type'] ?? null,
                    'payment_status'   => $paymentStatus,
                    'delivery_type'    => $validated['delivery_type']    ?? 'store',
                    'delivery_address' => $validated['delivery_address'] ?? null,
                    'delivery_phone'   => $validated['delivery_phone']   ?? null,
                    'notes'            => $validated['notes'] ?? null,
                ]);

                foreach ($saleItems as $si) {
                    $si['sale_id'] = $sale->id;
                    SaleItem::create($si);
                }

                if ($remaining > 0 && !empty($validated['customer_id'])) {
                    Customer::find($validated['customer_id'])?->increment('balance', $remaining);
                }

                return $sale;
            });

            return response()->json([
                'success'        => true,
                'message'        => 'تمت عملية البيع! رقم الفاتورة: ' . $sale->invoice_number,
                'invoice_number' => $sale->invoice_number,
                'sale_id'        => $sale->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Sale error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(Sale $sale)
    {
        abort_if($sale->user_id !== Auth::id(), 403);
        $sale->load('items.drug', 'customer');
        return view('sales.show', compact('sale'));
    }

    public function printInvoice(Sale $sale)
    {
        abort_if($sale->user_id !== Auth::id(), 403);
        $sale->load('items.drug', 'customer');
        return view('sales.print', compact('sale'));
    }
}