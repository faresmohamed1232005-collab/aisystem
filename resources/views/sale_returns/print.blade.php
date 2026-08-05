<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مرتجع {{ $saleReturn->return_number }}</title>
    @vite('resources/css/app.css')
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', sans-serif; }
        body { background: #fff; color: #111; direction: rtl; padding: 30px; }
        .header { text-align: center; border-bottom: 2px solid #f97316; padding-bottom: 16px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; font-weight: 800; color: #f97316; }
        .header p  { font-size: 12px; color: #666; margin-top: 4px; }
        .badge { display: inline-block; background: #fff7ed; color: #f97316; border: 1px solid #fed7aa;
                 border-radius: 20px; padding: 3px 12px; font-size: 11px; font-weight: 700; margin-top: 6px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .info-box  { background: #f9fafb; border-radius: 10px; padding: 12px; }
        .info-box .lbl { font-size: 10px; color: #9ca3af; margin-bottom: 4px; }
        .info-box .val { font-size: 13px; font-weight: 700; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #fff7ed; color: #9a3412; font-size: 11px; padding: 8px 10px;
                   border-bottom: 1px solid #fed7aa; text-align: right; }
        tbody td { font-size: 12px; padding: 9px 10px; border-bottom: 1px solid #f3f4f6; }
        tfoot td { font-size: 13px; font-weight: 800; padding: 10px; background: #fff7ed;
                   color: #c2410c; border-top: 2px solid #fed7aa; }
        .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #9ca3af; border-top: 1px solid #f3f4f6; padding-top: 14px; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>↩ إيصال مرتجع مبيعات</h1>
        <p>{{ Auth::user()->pharmacy_name }} — {{ Auth::user()->governorate }}</p>
        <span class="badge">{{ $saleReturn->return_number }}</span>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="lbl">فاتورة البيع الأصلية</div>
            <div class="val">{{ $saleReturn->sale->invoice_number }}</div>
        </div>
        <div class="info-box">
            <div class="lbl">العميل</div>
            <div class="val">{{ $saleReturn->customer?->name ?? 'عميل عام' }}</div>
        </div>
        <div class="info-box">
            <div class="lbl">تاريخ المرتجع</div>
            <div class="val">{{ $saleReturn->created_at->format('Y-m-d H:i') }}</div>
        </div>
        <div class="info-box">
            <div class="lbl">طريقة الاسترداد</div>
            <div class="val">
                @php $rm = ['cash'=>'رد نقدي','balance'=>'رصيد العميل','none'=>'بدون رد']; @endphp
                {{ $rm[$saleReturn->refund_method] ?? $saleReturn->refund_method }}
            </div>
        </div>
        @if($saleReturn->reason)
        <div class="info-box" style="grid-column:span 2">
            <div class="lbl">سبب الإرجاع</div>
            <div class="val">{{ $saleReturn->reason }}</div>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>المنتج</th>
                <th style="text-align:center">الكمية</th>
                <th style="text-align:center">السعر</th>
                <th style="text-align:center">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($saleReturn->items as $i => $item)
            <tr>
                <td>{{ $i+1 }}</td>
                <td style="font-weight:600">{{ $item->product?->name ?? 'منتج محذوف' }}</td>
                <td style="text-align:center">{{ $item->quantity }}</td>
                <td style="text-align:center">{{ number_format($item->price, 2) }} ج.م</td>
                <td style="text-align:center;font-weight:700;color:#c2410c">{{ number_format($item->subtotal, 2) }} ج.م</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:left">إجمالي المرتجع</td>
                <td style="text-align:center">{{ number_format($saleReturn->total, 2) }} ج.م</td>
            </tr>
        </tfoot>
    </table>

    @if($saleReturn->notes)
    <div style="background:#f9fafb;border-radius:10px;padding:12px;font-size:12px;color:#374151;margin-bottom:16px">
        <strong>ملاحظات:</strong> {{ $saleReturn->notes }}
    </div>
    @endif

    <div class="footer">
        تم الإصدار بواسطة نظام AI Pharmacy — {{ now()->format('Y-m-d H:i') }}
    </div>

    <div class="no-print" style="text-align:center;margin-top:20px">
        <button onclick="window.print()"
            style="background:#f97316;color:#fff;border:none;padding:10px 30px;border-radius:10px;font-family:Cairo,sans-serif;font-size:14px;cursor:pointer;font-weight:700">
            🖨 طباعة
        </button>
    </div>
    <script>window.onload=function(){window.print()}</script>
</body>
</html>
