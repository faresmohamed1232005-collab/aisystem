<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<title>جرد المخزن - {{ $reportDate }}</title>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
        color: #1f2937;
        padding: 20px;
        max-width: 900px;
        margin: 0 auto;
    }
    .header { text-align: center; margin-bottom: 15px; border-bottom: 3px solid #4f46e5; padding-bottom: 12px; }
    .header h1 { font-size: 22px; color: #4f46e5; margin: 0 0 6px; }
    .header .meta { font-size: 12px; color: #6b7280; }

    .summary { display: flex; gap: 10px; margin-bottom: 18px; }
    .summary-box { flex: 1; text-align: center; background: #f3f4f6; border-radius: 10px; padding: 12px; }
    .summary-box .num { font-size: 18px; font-weight: bold; color: #4f46e5; }
    .summary-box .label { font-size: 11px; color: #6b7280; margin-top: 3px; }

    table { width: 100%; border-collapse: collapse; }
    thead th {
        background: #4f46e5; color: #fff; padding: 8px 6px; font-size: 12px; text-align: center;
    }
    tbody td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-align: center; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    .name-cell { text-align: right !important; font-weight: bold; }

    .footer { text-align: center; font-size: 10px; color: #9ca3af; margin-top: 15px; }

    .print-btn {
        display: block; width: 200px; margin: 0 auto 20px;
        background: #4f46e5; color: #fff; border: none; padding: 12px;
        border-radius: 10px; font-size: 14px; font-weight: bold; cursor: pointer;
    }
    .print-btn:hover { background: #4338ca; }

    /* إخفاء الزرار عند الطباعة */
    @media print {
        .print-btn { display: none; }
        body { padding: 0; }
    }
</style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">
        🖨️ طباعة / حفظ PDF
    </button>

    <div class="header">
        <h1>تقرير جرد المخزن</h1>
        <div class="meta">تاريخ الجرد: {{ $reportDate }} | بواسطة: {{ $userName }}</div>
    </div>

    <div class="summary">
        <div class="summary-box">
            <div class="num">{{ number_format($totalItems) }}</div>
            <div class="label">عدد الأصناف</div>
        </div>
        <div class="summary-box">
            <div class="num">{{ number_format($totalQuantity, 0) }}</div>
            <div class="label">إجمالي الكمية (علب)</div>
        </div>
        <div class="summary-box">
            <div class="num">{{ number_format($totalValue, 2) }} ج.م</div>
            <div class="label">القيمة التقديرية</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 30%;">اسم الصنف</th>
                <th style="width: 12%;">الباركود</th>
                <th style="width: 16%;">الكمية</th>
                <th style="width: 14%;">سعر الوحدة</th>
                <th style="width: 14%;">القيمة</th>
          
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="name-cell">{{ $p->name_ar ?? $p->name_en }}</td>
                    <td>{{ $p->barcode ?? '—' }}</td>
                    <td>{{ $p->qty_display }}</td>
                    <td>{{ $p->custom_price ? number_format($p->custom_price, 2) . ' ج.م' : '—' }}</td>
                    <td>{{ $p->custom_price ? number_format($p->total_value, 2) . ' ج.م' : '—' }}</td>
             
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">تم إنشاء هذا التقرير تلقائيًا من نظام إدارة الصيدلية</div>

</body>
</html>