<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $sale->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eee; color: #000; font-family: Tahoma, Arial, sans-serif; direction: rtl; }
        .actions { width: 80mm; max-width: 100%; margin: 12px auto; display: flex; gap: 6px; justify-content: center; }
        .actions button, .actions a { border: 1px solid #222; background: #fff; color: #111; padding: 7px 10px; font: 12px Tahoma, Arial, sans-serif; text-decoration: none; cursor: pointer; border-radius: 5px; }
        .receipt { width: 72mm; min-height: 40mm; margin: 0 auto 18px; padding: 3mm 2mm; background: #fff; font-size: 11px; line-height: 1.45; }
        .center { text-align: center; }
        .pharmacy { font-size: 17px; font-weight: 700; overflow-wrap: anywhere; }
        .muted { font-size: 10px; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .meta, .total-row, .item-line { display: flex; justify-content: space-between; gap: 6px; }
        .meta span:last-child, .total-row span:last-child { text-align: left; }
        .item { padding: 4px 0; break-inside: avoid; }
        .item-name { font-weight: 700; overflow-wrap: anywhere; }
        .item-line { direction: rtl; font-size: 10px; }
        .grand { font-size: 14px; font-weight: 700; padding-top: 3px; }
        .thanks { margin-top: 8px; font-weight: 700; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            html, body { width: 80mm; background: #fff; }
            .actions { display: none !important; }
            .receipt { width: 72mm; margin: 0 auto; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">طباعة Receipt 80mm</button>
        <a href="{{ route('sales.print', ['sale' => $sale, 'format' => 'a4']) }}">عرض A4</a>
        <a href="{{ url()->previous() }}">رجوع</a>
    </div>

    @php
        $paymentLabels = [
            'cash' => 'نقدي',
            'card' => 'بطاقة',
            'insurance' => 'تأمين',
            'deferred' => 'آجل',
        ];
        $statusLabels = [
            'paid' => 'مسدد',
            'partial' => 'مدفوع جزئياً',
            'deferred' => 'آجل',
        ];
    @endphp

    <main class="receipt">
        <header class="center">
            <div class="pharmacy">{{ $sale->user->pharmacy_name ?: ($sale->user->name ?: 'الصيدلية') }}</div>
            @if($sale->user->address)<div class="muted">{{ $sale->user->address }}</div>@endif
            @if($sale->user->phone)<div class="muted">ت: {{ $sale->user->phone }}</div>@endif
        </header>

        <div class="divider"></div>
        <div class="center"><strong>فاتورة مبيعات</strong></div>
        <div class="meta"><span>رقم الفاتورة</span><span>{{ $sale->invoice_number }}</span></div>
        <div class="meta"><span>التاريخ</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="meta"><span>العميل</span><span>{{ $sale->customer->name ?? 'عميل نقدي' }}</span></div>
        <div class="meta"><span>الدفع</span><span>{{ $paymentLabels[$sale->payment_method] ?? $sale->payment_method }}</span></div>

        <div class="divider"></div>
        @foreach($sale->items as $item)
            <section class="item">
                <div class="item-name">{{ $item->drug->name_ar ?? ($item->drug->name_en ?? 'منتج محذوف') }}</div>
                <div class="item-line">
                    <span>{{ $item->quantity }} {{ $item->unit_name ?? 'وحدة' }} × {{ number_format($item->unit_price, 2) }}</span>
                    <strong>{{ number_format($item->subtotal, 2) }} ج.م</strong>
                </div>
            </section>
        @endforeach

        <div class="divider"></div>
        <div class="total-row"><span>المجموع</span><span>{{ number_format(($sale->total ?? 0) + ($sale->discount ?? 0), 2) }} ج.م</span></div>
        @if($sale->discount > 0)
            <div class="total-row"><span>الخصم</span><span>- {{ number_format($sale->discount, 2) }} ج.م</span></div>
        @endif
        <div class="total-row grand"><span>الإجمالي</span><span>{{ number_format($sale->total, 2) }} ج.م</span></div>
        <div class="total-row"><span>المدفوع</span><span>{{ number_format($sale->paid, 2) }} ج.م</span></div>
        <div class="total-row"><span>المتبقي</span><span>{{ number_format($sale->remaining ?? 0, 2) }} ج.م</span></div>
        <div class="total-row"><span>الحالة</span><span>{{ $statusLabels[$sale->payment_status ?? 'paid'] ?? '—' }}</span></div>

        <div class="divider"></div>
        <footer class="center thanks">شكراً لزيارتكم</footer>
    </main>
</body>
</html>
