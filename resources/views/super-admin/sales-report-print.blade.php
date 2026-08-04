<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير المبيعات التفصيلي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; box-sizing: border-box; }
        body { margin: 24px; color: #1e293b; }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .toolbar button {
            background: #6c5fe6;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }

        h2 { text-align: center; margin-bottom: 4px; }
        .sub { text-align: center; color: #64748b; font-size: 12px; margin-bottom: 10px; }

        .summary {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .summary div {
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
        }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: right; }
        th { background: #f1f5f9; }
        tbody tr:nth-child(even) { background: #fafbff; }
        tfoot td { font-weight: 800; background: #f8fafc; }
        td.num, th.num { text-align: center; }

        /* ══ إعدادات الطباعة ══ */
        @media print {
            .toolbar { display: none; }
            body { margin: 0; }
            @page { size: A4; margin: 15mm; }
            thead { display: table-header-group; } /* تكرار الهيدر في كل صفحة */
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div style="font-weight:700">تقرير المبيعات التفصيلي بالأدوية</div>
        <button onclick="window.print()">🖨️ طباعة / حفظ PDF</button>
    </div>

    <h2>تقرير المبيعات التفصيلي بالأدوية</h2>
    <div class="sub">
        الفلتر: {{ $filtersLabel }} — تاريخ الإصدار: {{ now()->format('Y/m/d h:i A') }}
    </div>

    <div class="summary">
        <div>عدد الأصناف: {{ $drugs->count() }}</div>
        <div>إجمالي الكمية المباعة: {{ number_format($grandQty) }}</div>
        <div>إجمالي المبيعات: {{ number_format($grandRevenue, 2) }} ج.م</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>اسم الدواء</th>
                <th>الفئة</th>
                <th class="num">الكمية المباعة</th>
                <th class="num">متوسط السعر</th>
                <th class="num">إجمالي المبيعات</th>
                <th class="num">عدد الصيدليات البائعة</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($drugs as $i => $drug)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $drug->name }}</td>
                    <td>{{ $drug->category ?? '—' }}</td>
                    <td class="num">{{ number_format($drug->total_qty) }}</td>
                    <td class="num">{{ number_format($drug->avg_price, 2) }}</td>
                    <td class="num">{{ number_format($drug->total_revenue, 2) }}</td>
                    <td class="num">{{ $drug->pharmacies_count }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8">لا توجد بيانات مبيعات مطابقة للفلتر</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">الإجمالي</td>
                <td class="num">{{ number_format($grandQty) }}</td>
                <td class="num">—</td>
                <td class="num">{{ number_format($grandRevenue, 2) }}</td>
                <td class="num">—</td>
            </tr>
        </tfoot>
    </table>

    <script>
        window.onload = () => setTimeout(() => window.print(), 400);
    </script>

</body>
</html>