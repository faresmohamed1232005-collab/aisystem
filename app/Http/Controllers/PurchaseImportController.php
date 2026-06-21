<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseImportController extends Controller
{
    /* صفحة استيراد فاتورة شراء من صورة */
    public function index()
    {
        return view('purchases.import');
    }

    /* استخراج بيانات الفاتورة من الصورة عبر OpenAI Vision
       يستقبل إما صورة واحدة (image) أو عدة شرائح متراكبة (images[]) من نفس الفاتورة */
    public function extract(Request $request)
    {
        $request->validate([
            'image'     => 'sometimes|image|mimes:jpeg,jpg,png,webp|max:8192',
            'images'    => 'sometimes|array|min:1|max:12',
            'images.*'  => 'image|mimes:jpeg,jpg,png,webp|max:8192',
        ]);

        // اجمع الصور: قد تكون شريحة واحدة أو شرائح متراكبة
        $files = $request->file('images');
        if (empty($files)) {
            $single = $request->file('image');
            $files  = $single ? [$single] : [];
        }
        if (empty($files)) {
            return response()->json(['success' => false, 'message' => 'لم يتم رفع أي صورة.'], 422);
        }

        $apiKey = config('services.openai.key');
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'مفتاح OpenAI غير مضبوط في الإعدادات (OPENAI_API_KEY).',
            ], 500);
        }

        try {
            // حوّل كل شريحة إلى data URL
            $imageContents = [];
            foreach ($files as $file) {
                $base64  = base64_encode(file_get_contents($file->getRealPath()));
                $mime    = $file->getMimeType() ?: 'image/jpeg';
                $dataUrl = "data:{$mime};base64,{$base64}";
                $imageContents[] = ['type' => 'image_url', 'image_url' => ['url' => $dataUrl, 'detail' => 'high']];
            }
            $sliceCount = count($imageContents);

            $sliceNote = $sliceCount > 1
                ? "ملاحظة مهمة: الصور المرفقة ({$sliceCount} صور) هي شرائح أفقية متتالية ومتراكبة من فاتورة واحدة، مرتبة من أعلى الفاتورة إلى أسفلها. الشرائح متداخلة عمداً (الجزء السفلي من كل شريحة يظهر في أعلى الشريحة التالية) لضمان عدم قص أي صف. ادمج كل الشرائح كأنها فاتورة واحدة، واقرأ كل صنف مرة واحدة فقط — لا تكرّر الصنف الذي يظهر في منطقة التراكب بين شريحتين."
                : "الصورة المرفقة هي فاتورة كاملة.";

            $prompt = <<<PROMPT
أنت خبير في قراءة فواتير شراء الأدوية المصرية. اقرأ الصورة/الصور بدقة حرفية شديدة واستخرج كل البيانات.

{$sliceNote}

أعد JSON فقط بالشكل التالي (بدون أي شرح):
{
  "supplier_name": "اسم المورد/الشركة الموردة كما هو مكتوب أعلى الفاتورة أو null",
  "invoice_number": "رقم الفاتورة أو null",
  "invoice_date": "تاريخ الفاتورة بصيغة YYYY-MM-DD أو null",
  "invoice_total": صافي إجمالي الفاتورة المطبوع في الأسفل (رقم) أو null,
  "items_count": العدد الكلي لأسطر الأصناف التي رأيتها فعلياً في الفاتورة (رقم صحيح),
  "items": [
    {
      "name": "اسم الصنف/الدواء حرفياً كما هو مكتوب تماماً في الفاتورة",
      "quantity": رقم الكمية,
      "price": سعر الوحدة المطبوع في عمود السعر (سعر الجمهور),
      "discount_percent": نسبة الخصم في عمود الخصم كرقم (مثال 34 وليس 0.34) أو 0,
      "line_total": إجمالي قيمة هذا السطر المطبوع في عمود الإجمالي/القيمة إن وُجد (رقم) أو null,
      "expiry_date": "تاريخ الصلاحية بصيغة YYYY-MM-DD أو null",
      "batch": "رقم التشغيلة إن وُجد أو null"
    }
  ]
}

قواعد صارمة:
- الاسم: انسخه حرفياً بنفس الكلمات والترتيب والأرقام كما هو مطبوع في الفاتورة (مثال: "أوجمنتين 1جم 14 قرص"). لا تترجمه ولا تختصره ولا تصححه ولا تضف من عندك. إن كان الخط غير واضح اكتب أقرب قراءة ممكنة فقط.
- بعض الفواتير مقسّمة إلى عمودين جنب بعض (يمين ويسار) — اقرأ كل صنف في صف منفصل في قائمة items.
- لا تكرّر أي صنف. كل سطر فعلي في الفاتورة = عنصر واحد فقط في items. ممنوع تكرار نفس الصنف.
- items_count = عدد الأسطر الحقيقي في الفاتورة. يجب أن يساوي طول قائمة items. تأكد أنك لم تنسَ أي سطر ولم تكرّر أي سطر.
- line_total: إن كان للفاتورة عمود "الإجمالي" أو "القيمة" لكل سطر، انسخ الرقم المطبوع كما هو. إن لم يوجد عمود إجمالي للسطر اجعله null.
- عمود "السعر" هو سعر الوحدة (سعر الجمهور) وليس الإجمالي.
- discount_percent رقم النسبة المئوية كما هو (لو مكتوب 34 رجّع 34).
- إذا كان تاريخ الصلاحية شهر/سنة فقط (مثل 02/2029) اجعله أول يوم في الشهر 2029-02-01.
- حوّل أي تاريخ بصيغة DD/MM/YYYY إلى YYYY-MM-DD، وانتبه جيداً للسنة (2029 وليس 2024 أو 2039).
- أرقام عشرية بنقطة عشرية. لا تضع فواصل آلاف.
أعد JSON مضغوط صحيح فقط.
PROMPT;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(180)->post('https://api.openai.com/v1/chat/completions', [
                'model'           => 'gpt-4o',
                'temperature'     => 0,
                'max_tokens'      => 16000,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [[
                    'role'    => 'user',
                    'content' => array_merge(
                        [['type' => 'text', 'text' => $prompt]],
                        $imageContents
                    ),
                ]],
            ]);

            if (!$response->successful()) {
                $err = $response->json('error.message', 'فشل الاتصال بـ OpenAI');
                Log::error('Invoice OCR error: ' . $err);
                return response()->json(['success' => false, 'message' => 'خطأ في القراءة: ' . $err], 500);
            }

            $finish  = $response->json('choices.0.finish_reason');
            $content = $response->json('choices.0.message.content', '');
            $data    = json_decode($content, true);

            if (!is_array($data) || empty($data['items'])) {
                $msg = $finish === 'length'
                    ? 'الفاتورة كبيرة جداً وتعذّر إكمال قراءتها. جرّب تصوير نصف الفاتورة في كل مرة.'
                    : 'لم أتمكن من قراءة أصناف من الصورة. جرّب صورة أوضح.';
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            // إزالة الصفوف المكررة الناتجة عن مناطق التراكب بين الشرائح
            $rawItems = $this->dedupeRows($data['items']);

            // تطبيع البيانات وحساب سعر الشراء من الخصم
            $items = [];
            foreach ($rawItems as $row) {
                $name = trim($row['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $price    = $this->num($row['price'] ?? 0);          // سعر البيع (الجمهور)
                $discount = $this->num($row['discount_percent'] ?? 0);
                $qty      = $this->num($row['quantity'] ?? 1) ?: 1;

                // سعر الشراء = السعر بعد الخصم
                $purchase = round($price * (1 - $discount / 100), 2);

                // الإجمالي المطبوع للسطر (لو الموديل قراه) — للتحقق
                $printedLine = isset($row['line_total']) && $row['line_total'] !== null
                    ? $this->num($row['line_total'])
                    : null;

                // مطابقة الصنف مع كتالوج الأدوية في السيستم
                $match = $this->matchDrug($name);

                $items[] = [
                    'name'           => $name,                       // الاسم كما قُرئ من الفاتورة
                    'quantity'       => $qty,
                    'selling_price'  => round($price, 2),
                    'discount'       => $discount,
                    'purchase_price' => $purchase,
                    'line_total'     => round($purchase * $qty, 2),  // إجمالي تكلفة الصنف (محسوب)
                    'printed_line'   => $printedLine ?: null,        // إجمالي السطر المطبوع في الفاتورة (للتحقق)
                    'expiry'         => $this->date($row['expiry_date'] ?? null),
                    'batch'          => $row['batch'] ?? null,
                    'match'          => $match['drug'],              // الصنف المقترح من السيستم أو null
                    'match_exact'    => $match['exact'],             // مطابقة تامة؟
                ];
            }

            $computedTotal = round(array_sum(array_column($items, 'line_total')), 2);
            $printedTotal  = isset($data['invoice_total']) ? $this->num($data['invoice_total']) : null;
            // العدد الذي قال الموديل إنه رآه في الفاتورة (للتحقق من اكتمال القراءة)
            $reportedCount = isset($data['items_count']) ? (int) $this->num($data['items_count']) : null;

            return response()->json([
                'success'  => true,
                'supplier' => $data['supplier_name'] ?? null,
                'invoice'  => [
                    'number'         => $data['invoice_number'] ?? null,
                    'date'           => $this->date($data['invoice_date'] ?? null),
                    'printed_total'  => $printedTotal ?: null,   // إجمالي الفاتورة المطبوع (للمقارنة)
                    'computed_total' => $computedTotal,          // مجموع تكلفة الأصناف المحسوب
                    'reported_count' => $reportedCount,          // عدد الأصناف الذي رآه الموديل
                    'extracted_count'=> count($items),           // عدد الأصناف المستخرجة فعلياً
                    'slices'         => $sliceCount,             // عدد الشرائح المرسلة
                ],
                'items'    => $items,
            ]);

        } catch (\Throwable $e) {
            Log::error('Invoice import failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطأ غير متوقع: ' . $e->getMessage()], 500);
        }
    }

    /* إزالة الصفوف المكررة الناتجة عن تراكب الشرائح
       (نفس الاسم المطبّع + نفس الكمية تقريباً + نفس السعر تقريباً = صف مكرر) */
    private function dedupeRows(array $rows): array
    {
        $seen = [];
        $out  = [];
        foreach ($rows as $row) {
            $name = trim($row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $key = $this->normalizeName($name)
                . '|' . $this->num($row['quantity'] ?? 0)
                . '|' . $this->num($row['price'] ?? 0);
            if (isset($seen[$key])) {
                continue; // صف مكرر من منطقة التراكب
            }
            $seen[$key] = true;
            $out[] = $row;
        }
        return $out;
    }

    /* كلمات وصفية تُتجاهل عند المطابقة */
    private const NOISE_WORDS = [
        'جديد', 'حجم', 'سعر', 'عرض', 'اقراص', 'قرص', 'كبسولة', 'كبسولات', 'امبول',
        'حقن', 'فيال', 'كريم', 'مرهم', 'جل', 'شراب', 'نقط', 'نقطة', 'لبوس', 'اقماع',
        'علبة', 'عبوة', 'كيس', 'اكياس', 'ق', 'ج', 'مجم', 'مج', 'جم', 'جرام', 'مل', 'وحدة',
    ];

    /* تطبيع الاسم العربي للمقارنة */
    private function normalizeName(string $s): string
    {
        $s = trim(mb_strtolower($s));
        // إزالة التشكيل
        $s = preg_replace('/[\x{064B}-\x{0652}]/u', '', $s);
        // توحيد الحروف
        $s = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $s);
        $s = str_replace('ة', 'ه', $s);
        $s = str_replace('ى', 'ي', $s);
        // إزالة الرموز والنسب المئوية
        $s = preg_replace('/[%،,.\-_\/()]+/u', ' ', $s);
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /* استخراج الكلمات المميزة (تجاهل الوصفية والأرقام) */
    private function significantTokens(string $normalized): array
    {
        $out = [];
        foreach (preg_split('/\s+/u', $normalized) as $t) {
            $t = preg_replace('/[0-9]/', '', $t); // شيل الأرقام الملتصقة (100مجم → مجم)
            if (mb_strlen($t) >= 3 && !in_array($t, self::NOISE_WORDS, true)) {
                $out[] = $t;
            }
        }
        return array_values(array_unique($out));
    }

    /* مطابقة اسم الصنف المقروء مع كتالوج الأدوية */
    private function matchDrug(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['drug' => null, 'exact' => false];
        }

        $normName = $this->normalizeName($name);
        $tokens   = $this->significantTokens($normName);
        if (empty($tokens)) {
            return ['drug' => null, 'exact' => false];
        }

        // الكلمة المرساة = الأطول (غالباً الاسم التجاري المميز)
        usort($tokens, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
        $anchor = $tokens[0];

        // مرشّحون يحتوون الكلمة المميزة
        $candidates = Drug::where('name_ar', 'like', "%{$anchor}%")
            ->orWhere('name_en', 'like', "%{$anchor}%")
            ->orderByDesc('popularity')
            ->limit(40)
            ->get();

        if ($candidates->isEmpty()) {
            return ['drug' => null, 'exact' => false];
        }

        // تقييم كل مرشّح بعدد الكلمات المشتركة
        $best = null;
        $bestScore = 0;
        foreach ($candidates as $cand) {
            $candNorm = $this->normalizeName($cand->name_ar ?: $cand->name_en ?: '');
            if ($candNorm === $normName) {
                return ['drug' => $this->drugBrief($cand), 'exact' => true]; // تطابق تام بعد التطبيع
            }
            $score = 0;
            foreach ($tokens as $tk) {
                if (mb_strpos($candNorm, $tk) !== false) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $cand;
            }
        }

        // نرجّع الأفضل كاقتراح يحتاج تأكيد (نطلب تطابق كلمة مميزة واحدة على الأقل)
        return ['drug' => ($best && $bestScore >= 1) ? $this->drugBrief($best) : null, 'exact' => false];
    }

    /* بيانات مختصرة للصنف للعرض في صفحة المراجعة */
    private function drugBrief(Drug $d): array
    {
        return [
            'id'          => $d->id,
            'name'        => $d->name_ar ?: $d->name_en,
            'barcode'     => $d->barcode,
            'category'    => $d->category,
            'major_units' => max(1, (int) $d->major_units),
            'minor_units' => max(1, (int) $d->minor_units),
        ];
    }

    /* تنظيف رقم من فواصل الآلاف أو رموز */
    private function num($v): float
    {
        if (is_numeric($v)) {
            return (float) $v;
        }
        $clean = preg_replace('/[^\d.\-]/', '', (string) $v);
        return is_numeric($clean) ? (float) $clean : 0;
    }

    /* تطبيع التاريخ لصيغة Y-m-d */
    private function date($v): ?string
    {
        if (empty($v)) {
            return null;
        }
        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
