{{-- resources/views/sales/print.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة مبيعات #{{ $sale->invoice_number }}</title>
    <style>
        /* ===== خطوط ===== */
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

        /* ===== ريسيت ===== */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: #f5f5f5;
            color: #1a1a2e;
            font-size: 14px;
            direction: rtl;
        }

        /* ===== حاوية الفاتورة ===== */
        .invoice-wrapper {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
            overflow: hidden;
        }

        /* ===== الهيدر ===== */
        .invoice-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #fff;
            padding: 32px 36px 24px;
            position: relative;
        }

        .invoice-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 20px;
            background: #fff;
            border-radius: 50% 50% 0 0 / 20px 20px 0 0;
        }

        .header-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .pharmacy-name {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -0.5px;
        }

        .pharmacy-sub {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 4px;
        }

        .invoice-badge {
            text-align: left;
        }

        .invoice-badge .invoice-type {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.75;
        }

        .invoice-badge .invoice-num {
            font-size: 22px;
            font-weight: 900;
            font-family: monospace;
        }

        /* ===== معلومات الفاتورة ===== */
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
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 4px;
        }

        .meta-block .meta-val {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
        }

        .meta-block .meta-sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* ===== جدول الأصناف ===== */
        .items-section {
            padding: 20px 36px;
        }

        .items-section h3 {
            font-size: 13px;
            font-weight: 700;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead tr {
            background: #f3f4f6;
        }

        .items-table th {
            padding: 10px 12px;
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            text-align: right;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: left;
        }

        .items-table td {
            padding: 10px 12px;
            font-size: 13px;
            color: #374151;
            border-bottom: 1px solid #f9fafb;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .items-table .product-name {
            font-weight: 600;
        }

        .items-table .product-cat {
            font-size: 11px;
            color: #a5b4fc;
            margin-top: 2px;
        }

        .items-table .price-val {
            font-family: monospace;
            font-weight: 600;
        }

        /* ===== الإجماليات ===== */
        .totals-section {
            padding: 0 36px 24px;
            display: flex;
            justify-content: flex-end;
        }

        .totals-box {
            width: 280px;
            background: #fafafa;
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid #e5e7eb;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 13px;
            color: #6b7280;
            border-bottom: 1px solid #f3f4f6;
        }

        .total-row:last-child {
            border-bottom: none;
        }

        .total-row.main {
            font-size: 16px;
            font-weight: 900;
            color: #6366f1;
            padding-top: 10px;
            margin-top: 4px;
        }

        .total-row .val {
            font-weight: 700;
            color: #1f2937;
        }

        .total-row.discount .val {
            color: #f97316;
        }

        .total-row.main .val {
            color: #6366f1;
            font-size: 18px;
        }

        /* ===== الفوتر ===== */
        .invoice-footer {
            background: #f9fafb;
            border-top: 1px dashed #e5e7eb;
            padding: 16px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ede9fe;
            color: #7c3aed;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 700;
        }

        .status-badge {
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 700;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-partial {
            background: #ffedd5;
            color: #9a3412;
        }

        .status-deferred {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ===== زرار الطباعة (يختفي عند الطباعة) ===== */
        .print-actions {
            max-width: 800px;
            margin: 0 auto 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            padding: 0 10px;
        }

        .btn-print {
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 24px;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background .2s;
        }

        .btn-print:hover {
            background: #4f46e5;
        }

        .btn-back {
            background: #f3f4f6;
            color: #374151;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background: #e5e7eb;
        }

        /* ===== طباعة ===== */
        @media print {
            @page {
                size: A4;
                margin: 10mm 12mm;
            }

            body {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color-adjust: exact;
            }

            .print-actions {
                display: none !important;
            }

            .invoice-wrapper {
                margin: 0;
                box-shadow: none;
                border-radius: 0;
                border: none;
            }

            .invoice-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* ===== ريسبونسيف للموبايل ===== */
        @media screen and (max-width: 600px) {
            .invoice-meta {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .invoice-header,
            .invoice-meta,
            .items-section,
            .totals-section,
            .invoice-footer {
                padding-right: 18px;
                padding-left: 18px;
            }

            .header-top {
                flex-direction: column;
                gap: 12px;
            }

            .totals-box {
                width: 100%;
            }

            .invoice-footer {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    {{-- ===== أزرار الطباعة والرجوع ===== --}}
    <div class="print-actions">
        <button class="btn-print" onclick="window.print()">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                <rect x="6" y="14" width="12" height="8" rx="1" />
            </svg>
            طباعة الفاتورة
        </button>
        <a href="{{ url()->previous() }}" class="btn-back">
            ← رجوع
        </a>
    </div>

    {{-- ===== الفاتورة ===== --}}
    <div class="invoice-wrapper">

        {{-- الهيدر --}}
        <div class="invoice-header">
            <div class="header-top">
                <div>
                    <div class="pharmacy-name">🏥
                        {{ $sale->user->pharmacy_name ?? (auth()->user()->pharmacy_name ?? 'الصيدلية') }}</div>
                    <div class="pharmacy-sub">{{ $sale->user->address ?? (auth()->user()->address ?? '') }}</div>
                </div>
                <div class="invoice-badge">
                    <div class="invoice-type">Sales Invoice</div>
                    <div class="invoice-num">#{{ $sale->invoice_number }}</div>
                </div>
            </div>
        </div>

        {{-- معلومات الفاتورة --}}
        <div class="invoice-meta">
            <div class="meta-block">
                <label>التاريخ والوقت</label>
                <div class="meta-val">{{ $sale->created_at->format('Y-m-d') }}</div>
                <div class="meta-sub">{{ $sale->created_at->format('H:i') }}</div>
            </div>

            <div class="meta-block">
                <label>العميل</label>
                @if ($sale->customer)
                    <div class="meta-val">{{ $sale->customer->name }}</div>
                    <div class="meta-sub" style="font-family:monospace">{{ $sale->customer->code }}</div>
                @else
                    <div class="meta-val" style="color:#d1d5db">عميل نقدي</div>
                @endif
            </div>

            <div class="meta-block">
                <label>طريقة الدفع</label>
                @php
                    $payLabels = [
                        'cash' => 'كاش 💵',
                        'card' => 'بطاقة 💳',
                        'insurance' => 'تأمين 🏥',
                        'deferred' => 'آجل 📋',
                    ];
                    $cardLabels = ['visa' => 'فيزا', 'instapay' => 'إنستاباي', 'wallet' => 'محفظة'];
                    $methodLabel = $payLabels[$sale->payment_method] ?? $sale->payment_method;
                    if ($sale->payment_method === 'card' && !empty($sale->card_type)) {
                        $methodLabel .= ' / ' . ($cardLabels[$sale->card_type] ?? $sale->card_type);
                    }
                @endphp
                <div class="meta-val">{{ $methodLabel }}</div>
            </div>

            <div class="meta-block">
                <label>حالة الدفع</label>
                @php
                    $statusMap = [
                        'paid' => ['✅ مسدد بالكامل', 'status-paid'],
                        'partial' => ['⏳ مدفوع جزئياً', 'status-partial'],
                        'deferred' => ['🔴 آجل', 'status-deferred'],
                    ];
                    [$sLabel, $sClass] = $statusMap[$sale->payment_status ?? 'paid'] ?? ['—', ''];
                @endphp
                <span class="status-badge {{ $sClass }}">{{ $sLabel }}</span>
                @if (isset($sale->remaining) && $sale->remaining > 0)
                    <div class="meta-sub" style="color:#ef4444;margin-top:6px">
                        متبقي: {{ number_format($sale->remaining, 2) }} ج.م
                    </div>
                @endif
            </div>
        </div>

        {{-- جدول الأصناف --}}
        <div class="items-section">
            <h3>تفاصيل الأصناف</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المنتج</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $i => $item)
                        <tr>
                            <td style="color:#9ca3af;font-size:12px">{{ $i + 1 }}</td>
                            <td>
                                <div class="product-name">
                                    {{ $item->drug->name_ar ?? ($item->drug->name_en ?? 'منتج محذوف') }}</div>
                                @if (optional($item->drug)->category)
                                    <div class="product-cat">{{ $item->drug->category }}</div>
                                @endif
                            </td>
                            <td><span
                                    style="background:#ede9fe;color:#6d28d9;padding:2px 10px;border-radius:12px;font-weight:700;font-size:12px">{{ $item->quantity }}</span>
                            </td>
                            <td class="price-val">{{ number_format($item->unit_price, 2) }} ج.م</td>
                            <td class="price-val" style="color:#6366f1">{{ number_format($item->subtotal, 2) }} ج.م
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- الإجماليات --}}
        <div class="totals-section">
            <div class="totals-box">
                <div class="total-row">
                    <span>المجموع الفرعي</span>
                    <span class="val">{{ number_format($sale->subtotal ?? $sale->total + $sale->discount, 2) }}
                        ج.م</span>
                </div>
                @if ($sale->discount > 0)
                    <div class="total-row discount">
                        <span>الخصم</span>
                        <span class="val">- {{ number_format($sale->discount, 2) }} ج.م</span>
                    </div>
                @endif
                <div class="total-row main">
                    <span>الإجمالي النهائي</span>
                    <span class="val">{{ number_format($sale->total, 2) }} ج.م</span>
                </div>
            </div>
        </div>

        {{-- الفوتر --}}
        <div class="invoice-footer">
            <div style="font-size:12px;color:#9ca3af">
                تم إصدار هذه الفاتورة بواسطة النظام الإلكتروني
            </div>
            <div style="font-size:12px;color:#6b7280;text-align:left">
                {{ $sale->user->phone ?? (auth()->user()->phone ?? '') }}
            </div>
        </div>

    </div>

</body>

</html>
