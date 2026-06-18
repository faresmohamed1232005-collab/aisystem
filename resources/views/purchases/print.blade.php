{{-- resources/views/purchases/print.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة شراء #{{ $invoice->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: #f5f5f5;
            color: #1a1a2e;
            font-size: 14px;
            direction: rtl;
        }

        .invoice-wrapper {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            overflow: hidden;
        }

        /* ===== هيدر أخضر للمشتريات ===== */
        .invoice-header {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #fff;
            padding: 32px 36px 24px;
            position: relative;
        }
        .invoice-header::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            height: 20px;
            background: #fff;
            border-radius: 50% 50% 0 0 / 20px 20px 0 0;
        }
        .header-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }
        .pharmacy-name  { font-size: 26px; font-weight: 900; }
        .pharmacy-sub   { font-size: 12px; opacity: 0.8; margin-top: 4px; }
        .invoice-badge  { text-align: left; }
        .invoice-badge .invoice-type { font-size: 11px; letter-spacing: 2px; opacity: 0.75; }
        .invoice-badge .invoice-num  { font-size: 22px; font-weight: 900; font-family: monospace; }

        /* ===== ميتا ===== */
        .invoice-meta {
            padding: 24px 36px 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            border-bottom: 1px dashed #e5e7eb;
        }
        .meta-block label {
            font-size: 11px;
            color: #9ca3af;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 4px;
        }
        .meta-block .meta-val  { font-size: 14px; font-weight: 700; color: #1f2937; }
        .meta-block .meta-sub  { font-size: 12px; color: #6b7280; margin-top: 2px; }

        /* ===== جدول ===== */
        .items-section { padding: 20px 36px; }
        .items-section h3 {
            font-size: 13px; font-weight: 700;
            color: #059669; letter-spacing: 1px; margin-bottom: 12px;
        }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table thead tr { background: #f0fdf4; }
        .items-table th {
            padding: 10px 12px;
            font-size: 12px; font-weight: 700;
            color: #6b7280; text-align: right;
        }
        .items-table th:last-child,
        .items-table td:last-child { text-align: left; }
        .items-table td {
            padding: 10px 12px; font-size: 13px;
            color: #374151; border-bottom: 1px solid #f9fafb;
        }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .product-name { font-weight: 600; }
        .product-cat  { font-size: 11px; color: #6ee7b7; margin-top: 2px; }
        .price-val    { font-family: monospace; font-weight: 600; }

        /* ===== إجماليات ===== */
        .totals-section {
            padding: 0 36px 24px;
            display: flex;
            justify-content: flex-end;
        }
        .totals-box {
            width: 280px;
            background: #f0fdf4;
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid #bbf7d0;
        }
        .total-row {
            display: flex; justify-content: space-between;
            align-items: center; padding: 6px 0;
            font-size: 13px; color: #6b7280;
            border-bottom: 1px solid #dcfce7;
        }
        .total-row:last-child { border-bottom: none; }
        .total-row.main {
            font-size: 16px; font-weight: 900;
            color: #059669; padding-top: 10px; margin-top: 4px;
        }
        .total-row .val        { font-weight: 700; color: #1f2937; }
        .total-row.discount .val { color: #f97316; }
        .total-row.remaining .val { color: #ef4444; }
        .total-row.main .val   { color: #059669; font-size: 18px; }

        /* ===== ديون / متبقي ===== */
        .debt-banner {
            margin: 0 36px 20px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #b91c1c;
            font-weight: 600;
        }

        /* ===== فوتر ===== */
        .invoice-footer {
            background: #f9fafb;
            border-top: 1px dashed #e5e7eb;
            padding: 16px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .status-badge   { border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 700; }
        .status-paid    { background: #d1fae5; color: #065f46; }
        .status-partial { background: #ffedd5; color: #9a3412; }
        .status-unpaid  { background: #fee2e2; color: #991b1b; }

        /* ===== أزرار ===== */
        .print-actions {
            max-width: 800px; margin: 0 auto 20px;
            display: flex; gap: 10px; padding: 0 10px;
        }
        .btn-print {
            background: #059669; color: #fff; border: none;
            border-radius: 10px; padding: 10px 24px;
            font-family: 'Cairo', sans-serif; font-size: 14px; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; gap: 8px;
            transition: background .2s;
        }
        .btn-print:hover { background: #047857; }
        .btn-back {
            background: #f3f4f6; color: #374151; border: none;
            border-radius: 10px; padding: 10px 20px;
            font-family: 'Cairo', sans-serif; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: background .2s;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-back:hover { background: #e5e7eb; }

        /* ===== طباعة ===== */
        @media print {
            @page { size: A4; margin: 10mm 12mm; }
            body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-actions { display: none !important; }
            .invoice-wrapper { margin: 0; box-shadow: none; border-radius: 0; border: none; }
            .invoice-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        /* ===== موبايل ===== */
        @media screen and (max-width: 600px) {
            .invoice-meta    { grid-template-columns: 1fr; gap: 12px; }
            .invoice-header, .invoice-meta, .items-section,
            .totals-section, .invoice-footer, .debt-banner { padding-right: 18px; padding-left: 18px; }
            .header-top   { flex-direction: column; gap: 12px; }
            .totals-box   { width: 100%; }
            .invoice-footer { flex-direction: column; gap: 10px; }
            .items-table  { font-size: 11px; }
            .items-table th, .items-table td { padding: 7px 6px; }
        }
    </style>
</head>
<body>

{{-- أزرار --}}
<div class="print-actions">
    <button class="btn-print" onclick="window.print()">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8" rx="1"/>
        </svg>
        طباعة الفاتورة
    </button>
    <a href="{{ url()->previous() }}" class="btn-back">← رجوع</a>
</div>

<div class="invoice-wrapper">

    {{-- هيدر --}}
    <div class="invoice-header">
        <div class="header-top">
            <div>
                <div class="pharmacy-name">🏥 {{ config('app.pharmacy_name', 'صيدلية النور') }}</div>
                <div class="pharmacy-sub">{{ config('app.pharmacy_address', 'العنوان — رقم الترخيص') }}</div>
            </div>
            <div class="invoice-badge">
                <div class="invoice-type">Purchase Invoice</div>
                <div class="invoice-num">#{{ $invoice->invoice_number }}</div>
            </div>
        </div>
    </div>

    {{-- ميتا --}}
    <div class="invoice-meta">
        <div class="meta-block">
            <label>تاريخ الفاتورة</label>
            <div class="meta-val">{{ optional($invoice->invoice_date)->format('Y-m-d') ?? $invoice->created_at->format('Y-m-d') }}</div>
            <div class="meta-sub">{{ $invoice->created_at->format('H:i') }}</div>
        </div>

        <div class="meta-block">
            <label>المورد</label>
            @if($invoice->supplier)
                <div class="meta-val">{{ $invoice->supplier->name }}</div>
                <div class="meta-sub" style="font-family:monospace">{{ $invoice->supplier->code }}</div>
            @else
                <div class="meta-val" style="color:#d1d5db">— بدون مورد —</div>
            @endif
        </div>

        <div class="meta-block">
            <label>طريقة الدفع</label>
            @php
                $payLabels = ['cash'=>'كاش 💵','card'=>'بطاقة 💳','transfer'=>'تحويل 🏦','deferred'=>'آجل 📋'];
            @endphp
            <div class="meta-val">{{ $payLabels[$invoice->payment_method] ?? $invoice->payment_method }}</div>
        </div>

        <div class="meta-block">
            <label>حالة الدفع</label>
            @php
                $statusMap = [
                    'paid'     => ['✅ مسدد بالكامل', 'status-paid'],
                    'partial'  => ['⏳ مدفوع جزئياً',  'status-partial'],
                    'unpaid'   => ['🔴 غير مسدد',      'status-unpaid'],
                    'deferred' => ['🔴 آجل',            'status-unpaid'],
                ];
                [$sLabel, $sClass] = $statusMap[$invoice->payment_status] ?? ['—', ''];
            @endphp
            <span class="status-badge {{ $sClass }}">{{ $sLabel }}</span>
        </div>
    </div>

    {{-- بانر الديون --}}
    @if($invoice->remaining > 0)
    <div class="debt-banner">
        <span>⚠️</span>
        <span>المبلغ المتبقي للمورد: <strong>{{ number_format($invoice->remaining, 2) }} ج.م</strong></span>
    </div>
    @endif

    {{-- جدول الأصناف --}}
    <div class="items-section">
        <h3>تفاصيل الأصناف</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>سعر الشراء</th>
                    <th>سعر البيع</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $i => $item)
                <tr>
                    <td style="color:#9ca3af;font-size:12px">{{ $i + 1 }}</td>
                    <td>
                        <div class="product-name">{{ $item->product_name }}</div>
                        @if($item->category ?? optional($item->product)->category)
                            <div class="product-cat">{{ $item->category ?? $item->product->category }}</div>
                        @endif
                    </td>
                    <td><span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:12px;font-weight:700;font-size:12px">{{ $item->quantity }}</span></td>
                    <td class="price-val">{{ number_format($item->purchase_price, 2) }} ج.م</td>
                    <td class="price-val" style="color:#6b7280">{{ number_format($item->selling_price ?? 0, 2) }} ج.م</td>
                    <td class="price-val" style="color:#059669">{{ number_format($item->subtotal, 2) }} ج.م</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- إجماليات --}}
    <div class="totals-section">
        <div class="totals-box">
            <div class="total-row">
                <span>المجموع الفرعي</span>
                <span class="val">{{ number_format($invoice->subtotal ?? $invoice->net_total, 2) }} ج.م</span>
            </div>
            @if(($invoice->discount ?? 0) > 0)
            <div class="total-row discount">
                <span>الخصم</span>
                <span class="val">- {{ number_format($invoice->discount, 2) }} ج.م</span>
            </div>
            @endif
            @if(($invoice->tax ?? 0) > 0)
            <div class="total-row">
                <span>الضريبة</span>
                <span class="val">+ {{ number_format($invoice->tax, 2) }} ج.م</span>
            </div>
            @endif
            <div class="total-row main">
                <span>الإجمالي النهائي</span>
                <span class="val">{{ number_format($invoice->net_total, 2) }} ج.م</span>
            </div>
            @if($invoice->paid > 0 && $invoice->remaining > 0)
            <div class="total-row" style="padding-top:8px;margin-top:4px;border-top:1px solid #bbf7d0">
                <span>المدفوع</span>
                <span class="val" style="color:#059669">{{ number_format($invoice->paid, 2) }} ج.م</span>
            </div>
            <div class="total-row remaining">
                <span>المتبقي</span>
                <span class="val">{{ number_format($invoice->remaining, 2) }} ج.م</span>
            </div>
            @endif
        </div>
    </div>

    {{-- فوتر --}}
    <div class="invoice-footer">
        <div style="font-size:12px;color:#9ca3af">تم إصدار هذه الفاتورة بواسطة النظام الإلكتروني</div>
        <div style="font-size:12px;color:#6b7280;text-align:left">{{ config('app.pharmacy_phone', '') }}</div>
    </div>

</div>
</body>
</html>