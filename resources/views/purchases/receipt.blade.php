<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Receipt #{{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eee; color: #000; font-family: Tahoma, Arial, sans-serif; direction: rtl; }
        .actions { width: 80mm; max-width: 100%; margin: 12px auto; display: flex; gap: 6px; justify-content: center; }
        .actions button, .actions a { border: 1px solid #222; background: #fff; color: #111; padding: 7px 9px; font: 11px Tahoma, Arial, sans-serif; text-decoration: none; cursor: pointer; border-radius: 5px; }
        .receipt { width: 72mm; margin: 0 auto 18px; padding: 3mm 2mm; background: #fff; font-size: 11px; line-height: 1.45; }
        .center { text-align: center; }
        .title { font-size: 16px; font-weight: 700; }
        .muted { font-size: 10px; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .meta, .total-row, .item-line { display: flex; justify-content: space-between; gap: 6px; }
        .meta span:last-child, .total-row span:last-child { text-align: left; }
        .item { padding: 4px 0; break-inside: avoid; }
        .item-name { font-weight: 700; overflow-wrap: anywhere; }
        .item-line { font-size: 10px; }
        .expiry { font-size: 9px; }
        .grand { font-size: 14px; font-weight: 700; padding-top: 3px; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            html, body { width: 80mm; background: #fff; }
            .actions { display: none !important; }
            .receipt { width: 72mm; margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">طباعة Receipt 80mm</button>
        <a href="{{ route('purchases.print', ['invoice' => $invoice, 'format' => 'a4']) }}">عرض A4</a>
        <a href="{{ url()->previous() }}">رجوع</a>
    </div>

    @php
        $paymentLabels = ['cash' => 'نقدي', 'card' => 'بطاقة', 'transfer' => 'تحويل', 'deferred' => 'آجل'];
        $statusLabels = ['paid' => 'مسدد', 'partial' => 'مدفوع جزئياً', 'unpaid' => 'غير مسدد', 'deferred' => 'آجل'];
    @endphp

    <main class="receipt">
        <header class="center">
            <div class="title">إيصال فاتورة شراء</div>
            <div class="muted">{{ auth()->user()->pharmacy_name ?: (auth()->user()->name ?: 'الصيدلية') }}</div>
        </header>

        <div class="divider"></div>
        <div class="meta"><span>رقم الفاتورة</span><span>{{ $invoice->invoice_number }}</span></div>
        <div class="meta"><span>التاريخ</span><span>{{ optional($invoice->invoice_date)->format('d/m/Y') ?? $invoice->created_at->format('d/m/Y') }}</span></div>
        <div class="meta"><span>المورد</span><span>{{ $invoice->supplier->name ?? 'بدون مورد' }}</span></div>
        <div class="meta"><span>الدفع</span><span>{{ $paymentLabels[$invoice->payment_method] ?? $invoice->payment_method }}</span></div>

        <div class="divider"></div>
        @foreach($invoice->items as $item)
            <section class="item">
                <div class="item-name">{{ $item->product_name }}</div>
                <div class="item-line">
                    <span>{{ $item->quantity }} × {{ number_format($item->purchase_price, 2) }}</span>
                    <strong>{{ number_format($item->subtotal, 2) }} ج.م</strong>
                </div>
                @if($item->expiry_date)<div class="expiry">الصلاحية: {{ $item->expiry_date->format('d/m/Y') }}</div>@endif
            </section>
        @endforeach

        <div class="divider"></div>
        <div class="total-row"><span>المجموع</span><span>{{ number_format($invoice->total, 2) }} ج.م</span></div>
        @if($invoice->discount > 0)<div class="total-row"><span>الخصم</span><span>- {{ number_format($invoice->discount, 2) }} ج.م</span></div>@endif
        @if($invoice->extra > 0)<div class="total-row"><span>إضافي</span><span>{{ number_format($invoice->extra, 2) }} ج.م</span></div>@endif
        <div class="total-row grand"><span>الصافي</span><span>{{ number_format($invoice->net_total, 2) }} ج.م</span></div>
        <div class="total-row"><span>المدفوع</span><span>{{ number_format($invoice->paid, 2) }} ج.م</span></div>
        <div class="total-row"><span>المتبقي</span><span>{{ number_format($invoice->remaining, 2) }} ج.م</span></div>
        <div class="total-row"><span>الحالة</span><span>{{ $statusLabels[$invoice->payment_status] ?? '—' }}</span></div>
    </main>
</body>
</html>
