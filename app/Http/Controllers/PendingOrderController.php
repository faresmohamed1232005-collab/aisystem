<?php

namespace App\Http\Controllers;

use App\Models\PendingOrder;
use App\Models\PendingOrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Drug;
use App\Models\UserDrugInventory;

class PendingOrderController extends Controller
{
    // قائمة الطلبات المعلقة
    public function index()
    {
        $orders = PendingOrder::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->with(['customer', 'items.drug'])
            ->latest()
            ->get();

        $confirmedToday = PendingOrder::where('user_id', Auth::id())
            ->where('status', 'confirmed')
            ->whereDate('updated_at', today())
            ->count();

        return view('pending.index', compact('orders', 'confirmedToday'));
    }

    // حفظ طلب معلق جديد من صفحة البيع
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:drugs,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.qty_factor' => 'nullable|numeric|min:0.000001',
            'items.*.unit_key' => 'nullable|string',
            'items.*.unit_name' => 'nullable|string',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'delivery_type' => 'required|in:store,delivery',
            'delivery_address' => 'nullable|string|max:500',
            'delivery_phone' => 'nullable|string|max:20',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // لو توصيل، العنوان والتليفون مطلوبين
        if ($validated['delivery_type'] === 'delivery') {
            if (empty($validated['delivery_address'])) {
                return response()->json(['success' => false, 'message' => 'عنوان التوصيل مطلوب!'], 422);
            }
        }

        try {
            $order = DB::transaction(function () use ($validated) {
                $total = 0;
                $orderItems = [];
                $userId = Auth::id();

                foreach ($validated['items'] as $item) {
                    $drug = Drug::findOrFail($item['id']);

                    $inv = UserDrugInventory::where('user_id', $userId)
                        ->where('drug_id', $drug->id)
                        ->firstOrFail();

                    $qty = $item['qty'];
                    $qtyFactor = floatval($item['qty_factor'] ?? 1);
                    $consumed = $qty * $qtyFactor;
                    $unitPrice = floatval($item['unit_price'] ?? $inv->custom_price ?? $drug->new_price ?? 0);
                    $unitKey = $item['unit_key'] ?? 'pack';
                    $unitName = $item['unit_name'] ?? 'علبة';

                    if (floatval($inv->quantity) < $consumed) {
                        throw new \Exception('الكمية المطلوبة من "' . $drug->name_ar . '" غير متوفرة. متوفر: ' . $inv->quantity);
                    }

                    $subtotal = $unitPrice * $qty;
                    $total += $subtotal;

                    $orderItems[] = [
                        'drug_id' => $drug->id,  //
                        'quantity' => $qty,
                        'qty_factor' => $qtyFactor,
                        'unit_key' => $unitKey,
                        'unit_name' => $unitName,
                        'unit_price' => $unitPrice,
                        'price' => $unitPrice,
                        'subtotal' => round($subtotal, 2),
                    ];
                }

                $order = PendingOrder::create([
                    'user_id' => $userId,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'order_number' => PendingOrder::generateNumber($userId),
                    'delivery_type' => $validated['delivery_type'],
                    'delivery_address' => $validated['delivery_address'] ?? null,
                    'delivery_phone' => $validated['delivery_phone'] ?? null,
                    'total' => $total,
                    'discount' => floatval($validated['discount'] ?? 0),
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'pending',
                ]);

                foreach ($orderItems as $oi) {
                    $oi['pending_order_id'] = $order->id;
                    PendingOrderItem::create($oi);
                }

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الطلب! رقم الطلب: ' . $order->order_number,
                'order_number' => $order->order_number,
                'order_id' => $order->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Pending order error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // تأكيد الطلب وتحويله لفاتورة بيع
    public function confirm(Request $request, PendingOrder $pendingOrder)
    {
        abort_if($pendingOrder->user_id !== Auth::id(), 403);
        abort_if($pendingOrder->status !== 'pending', 422);

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,insurance,deferred',
            'card_type' => 'nullable|in:visa,instapay,wallet',
            'paid' => 'required|numeric|min:0',
        ], [
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'paid.required' => 'المبلغ المدفوع مطلوب',
        ]);

        if ($validated['payment_method'] === 'deferred' && !$pendingOrder->customer_id) {
            return back()->withErrors(['customer' => 'يجب تحديد عميل للبيع الآجل']);
        }

        try {
            DB::transaction(function () use ($pendingOrder, $validated) {

                $netTotal = $pendingOrder->net_total;
                $paid = floatval($validated['paid']);
                $remaining = max(0, $netTotal - $paid);

                $paymentStatus = 'paid';
                if ($validated['payment_method'] === 'deferred') {
                    $paymentStatus = $paid > 0 ? 'partial' : 'deferred';
                } elseif ($remaining > 0) {
                    $paymentStatus = 'partial';
                }

                // إنشاء الفاتورة
                $sale = Sale::create([
                    'user_id' => Auth::id(),
                    'customer_id' => $pendingOrder->customer_id,
                    'invoice_number' => 'INV-' . strtoupper(substr(uniqid(), -8)),
                    'total' => $netTotal,
                    'discount' => $pendingOrder->discount,
                    'paid' => $paid,
                    'remaining' => $remaining,
                    'payment_method' => $validated['payment_method'],
                    'card_type' => $validated['card_type'] ?? null,
                    'payment_status' => $paymentStatus,
                    'notes' => $pendingOrder->notes,
                ]);

                foreach ($pendingOrder->items as $item) {
                    $drug = Drug::lockForUpdate()->findOrFail($item->drug_id);

                    $inv = UserDrugInventory::where('user_id', Auth::id())
                        ->where('drug_id', $drug->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $qtyConsumed = $item->quantity * floatval($item->qty_factor ?? 1);

                    if (floatval($inv->quantity) < $qtyConsumed) {
                        throw new \Exception('الكمية المطلوبة من "' . $drug->name_ar . '" غير متوفرة. متوفر: ' . $inv->quantity);
                    }

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'drug_id' => $drug->id,
                        'quantity' => $item->quantity,
                        'qty_factor' => $item->qty_factor ?? 1,
                        'unit_key' => $item->unit_key ?? 'pack',
                        'unit_name' => $item->unit_name ?? 'علبة',
                        'unit_price' => $item->unit_price ?? $item->price,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'cost_price' => floatval($inv->cost_price ?? 0),
                    ]);

                    $inv->decrement('quantity', $qtyConsumed);
                }
                // تحديث رصيد العميل لو آجل
                if ($remaining > 0 && $pendingOrder->customer_id) {
                    $customer = Customer::find($pendingOrder->customer_id);
                    $customer?->increment('balance', $remaining);
                }

                // تحديث حالة الطلب
                $pendingOrder->update(['status' => 'confirmed']);
            });

            return back()->with('success', 'تم تأكيد الطلب وإنشاء الفاتورة بنجاح!');

        } catch (\Exception $e) {
            Log::error('Confirm order error: ' . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // حذف / إلغاء طلب معلق (المخزن ما اتأثرش فالترجيع مش مطلوب)
    public function destroy(PendingOrder $pendingOrder)
    {
        abort_if($pendingOrder->user_id !== Auth::id(), 403);
        abort_if($pendingOrder->status !== 'pending', 422);

        $pendingOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'تم إلغاء الطلب — المخزن لم يتأثر.');
    }

    // عدد الطلبات المعلقة (للـ badge في الناف بار)
    public function count()
    {
        return response()->json([
            'count' => PendingOrder::where('user_id', Auth::id())
                ->where('status', 'pending')
                ->count()
        ]);
    }
}