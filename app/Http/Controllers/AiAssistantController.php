<?php

namespace App\Http\Controllers;

use App\Services\Ai\ChatGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiAssistantController extends Controller
{
    public function chat(Request $request, ChatGateway $ai)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:20',
        ]);

        $userMessage = $request->input('message');
        $history     = $request->input('history', []);

        $context = $this->buildPharmacyContext($userMessage);

        $systemPrompt = <<<PROMPT
أنت "الصيدلي الذكي"، مساعد ذكاء اصطناعي متخصص في إدارة الصيدليات باللغة العربية.
أنت تعمل داخل نظام صيدلية اسمه "AI Pharmacy System".

بياناتك الحالية من قاعدة البيانات:
{$context}

قواعد مهمة جداً:
1. أجب دائماً بالعربية بأسلوب واضح ومنظم.
2. استخدم الأرقام والبيانات الحقيقية من السياق أعلاه فقط.
3. عند السؤال عن دواء معين → ابحث في قائمة "المنتجات المتاحة في المخزن" واذكر كميته وسعره وتاريخ انتهائه.
4. عند السؤال عن بدائل دواء → ابحث أولاً في مخزونك، ثم في قسم "بدائل من قاعدة البيانات الكاملة". إذا وجدت بديلاً غير موجود في مخزونك قل: "هذا البديل غير موجود في مخزونك حالياً".
5. عند السؤال عن الكميات المنخفضة → استخدم قسم "منتجات منخفضة الكمية".
6. عند السؤال عن انتهاء الصلاحية → استخدم قسم "أدوية تنتهي صلاحيتها".
7. عند السؤال عن المبيعات → استخدم أقسام إحصائيات المبيعات وآخر الفواتير.
8. عند السؤال عن أعلى الأدوية مبيعاً → استخدم قسم "أعلى الأدوية مبيعاً".
9. عند السؤال عن الموردين والديون → استخدم قسم "موردون بديون مستحقة".
10. إذا سُئلت عن جرعات أو معلومات طبية → أعط المعلومات الطبية الصحيحة مع التنويه باستشارة الطبيب.
11. إذا لم تجد المنتج في المخزن → قل "هذا المنتج غير موجود في مخزونك حالياً" ثم ابحث في قسم البدائل واذكرها مع توضيح أيها موجود في مخزونك وأيها لا.
12. كن ودوداً ومفيداً — رتّب الإجابات في قوائم واضحة.
PROMPT;

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach (array_slice($history, -8) as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                $messages[] = [
                    'role'    => in_array($msg['role'], ['user', 'assistant']) ? $msg['role'] : 'user',
                    'content' => substr($this->cleanText($msg['content']), 0, 500),
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $this->cleanText($userMessage)];

        // ✅ تنظيف كل النصوص قبل الإرسال لمنع خطأ json_encode
        $messages = $this->cleanArray($messages);

        // على الويب: مفتاح محلي → نداء مباشر. على الديسكتوب: يوجّه ChatGateway الطلب
        // للسيرفر المركزي (الذي يملك المفتاح) بمفتاح المزامنة.
        $result = $ai->chatCompletion([
            'model'       => config('services.openai.model', 'gpt-4o-mini'),
            'messages'    => $messages,
            'max_tokens'  => 1000,
            'temperature' => 0.7,
        ], timeout: 30);

        if (! $result['ok']) {
            return response()->json(['error' => '⚠️ ' . $result['error']], 500);
        }

        $reply = data_get($result['json'], 'choices.0.message.content');
        return response()->json(['reply' => $reply]);
    }

    // =====================================================
    // ✅ تنظيف UTF-8 — يمنع خطأ json_encode
    // =====================================================
    private function cleanText(?string $text): string
    {
        if ($text === null) return '';
        // إزالة أي bytes غير صالحة لـ UTF-8
        $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        // إزالة null bytes وأي control characters غير مرئية
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);
        return $clean ?? '';
    }

    private function cleanArray(array $arr): array
    {
        array_walk_recursive($arr, function (&$val) {
            if (is_string($val)) {
                $val = $this->cleanText($val);
            }
        });
        return $arr;
    }

    // =====================================================
    // بناء السياق
    // =====================================================
    private function buildPharmacyContext(string $question): string
    {
        $userId = Auth::id();
        $lines  = [];

        // ─────────────────────────────────────────────
        // 1. ملخص المخزن
        // ─────────────────────────────────────────────
        try {
            $summary = DB::table('user_drug_inventory as inv')
                ->where('inv.user_id', $userId)
                ->where('inv.branch_id', \App\Support\ActiveBranch::id())
                ->selectRaw('
                    COUNT(*) as total_items,
                    SUM(CASE WHEN inv.quantity > 0 THEN 1 ELSE 0 END) as in_stock,
                    SUM(CASE WHEN inv.quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN inv.quantity > 0
                             AND inv.quantity <= COALESCE(inv.min_quantity,5)
                             THEN 1 ELSE 0 END) as low_stock
                ')
                ->first();

            $totalDrugs = DB::table('drugs')->count();

            $lines[] = "=== ملخص المخزن ===";
            $lines[] = "إجمالي الأدوية في قاعدة البيانات: {$totalDrugs}";
            $lines[] = "أصناف مضافة لمخزونك: {$summary->total_items}";
            $lines[] = "أصناف متاحة (كمية > 0): {$summary->in_stock}";
            $lines[] = "أصناف نفدت (كمية = 0): {$summary->out_of_stock}";
            $lines[] = "أصناف منخفضة الكمية: {$summary->low_stock}";
        } catch (\Exception $e) {
            $lines[] = "=== ملخص المخزن: تعذر التحميل ===";
        }

        // ─────────────────────────────────────────────
        // 2. المنتجات المتاحة في مخزونك
        // ─────────────────────────────────────────────
        try {
            $products = DB::table('drugs')
                ->join('user_drug_inventory as inv', function ($j) use ($userId) {
                    $j->on('inv.drug_id', '=', 'drugs.id')
                      ->where('inv.user_id', '=', $userId)
                      ->where('inv.branch_id', '=', \App\Support\ActiveBranch::id());
                })
                ->where('inv.quantity', '>', 0)
                ->select(
                    'drugs.id',
                    'drugs.name_ar', 'drugs.name_en',
                    'drugs.active_ingredient', 'drugs.category',
                    'drugs.dosage_form', 'drugs.concentration', 'drugs.company',
                    'drugs.major_units', 'drugs.minor_units',
                    DB::raw("COALESCE(inv.strip_unit_name,'شريط') as strip_unit"),
                    DB::raw("COALESCE(inv.piece_unit_name,'حبة') as piece_unit"),
                    'inv.quantity',
                    DB::raw('COALESCE(inv.min_quantity,5) as min_qty'),
                    DB::raw('COALESCE(inv.custom_price, drugs.new_price, drugs.old_price, 0) as sell_price'),
                    'inv.cost_price', 'inv.expiry_date'
                )
                ->orderByDesc('inv.quantity')
                ->limit(80)
                ->get();

            if ($products->count() > 0) {
                $lines[] = "\n=== المنتجات المتاحة في المخزن ===";
                foreach ($products as $p) {
                    $name    = $this->cleanText($p->name_ar ?: $p->name_en);
                    $active  = $p->active_ingredient ? " | مادة فعالة: " . $this->cleanText($p->active_ingredient) : '';
                    $cat     = $p->category          ? " | فئة: "        . $this->cleanText($p->category)          : '';
                    $form    = $p->dosage_form       ? " | شكل: "        . $this->cleanText($p->dosage_form)       : '';
                    $conc    = $p->concentration     ? " | تركيز: "      . $this->cleanText($p->concentration)     : '';
                    $company = $p->company           ? " | شركة: "       . $this->cleanText($p->company)           : '';
                    $expire  = $p->expiry_date       ? " | انتهاء: {$p->expiry_date}"                              : '';
                    $major   = (int)$p->major_units > 1 ? " | {$p->major_units} {$p->strip_unit}/علبة" : '';
                    $minor   = (int)$p->minor_units > 1 ? " {$p->minor_units} {$p->piece_unit}/شريط"   : '';
                    $lines[] = "• {$name}{$active}{$cat}{$form}{$conc}{$company} | كمية: {$p->quantity} علبة | سعر: {$p->sell_price} ج{$major}{$minor}{$expire}";
                }
            } else {
                $lines[] = "\n=== لا توجد منتجات بكمية في المخزن حالياً ===";
            }
        } catch (\Exception $e) {
            $lines[] = "\n=== خطأ في تحميل المنتجات: " . $e->getMessage() . " ===";
        }

        // ─────────────────────────────────────────────
        // 3. بدائل من قاعدة البيانات الكاملة (25 ألف دواء)
        //    يبحث بالمادة الفعالة أو الاسم المذكور في السؤال
        // ─────────────────────────────────────────────
        try {
            // استخراج كلمات البحث من السؤال (أكثر من 3 حروف)
            $words = array_filter(
                preg_split('/[\s\،\,]+/', $question),
                fn($w) => mb_strlen($w) > 3
            );

            if (!empty($words)) {
                // جيب المواد الفعالة من مخزون المستخدم المطابقة للسؤال
                $activeIngredients = DB::table('drugs')
                    ->join('user_drug_inventory as inv', function ($j) use ($userId) {
                        $j->on('inv.drug_id', '=', 'drugs.id')
                          ->where('inv.user_id', '=', $userId);
                    })
                    ->where(function ($q) use ($words) {
                        foreach ($words as $w) {
                            $q->orWhere('drugs.name_ar', 'like', "%{$w}%")
                              ->orWhere('drugs.name_en', 'like', "%{$w}%")
                              ->orWhere('drugs.active_ingredient', 'like', "%{$w}%");
                        }
                    })
                    ->whereNotNull('drugs.active_ingredient')
                    ->pluck('drugs.active_ingredient')
                    ->unique()
                    ->take(3)
                    ->toArray();

                // لو ملقيناش في المخزن، ابحث في كل الأدوية
                if (empty($activeIngredients)) {
                    $activeIngredients = DB::table('drugs')
                        ->where(function ($q) use ($words) {
                            foreach ($words as $w) {
                                $q->orWhere('name_ar', 'like', "%{$w}%")
                                  ->orWhere('name_en', 'like', "%{$w}%")
                                  ->orWhere('active_ingredient', 'like', "%{$w}%");
                            }
                        })
                        ->whereNotNull('active_ingredient')
                        ->pluck('active_ingredient')
                        ->unique()
                        ->take(3)
                        ->toArray();
                }

                if (!empty($activeIngredients)) {
                    // جيب كل الأدوية بنفس المواد الفعالة من قاعدة البيانات الكاملة
                    $alternatives = DB::table('drugs')
                        ->where(function ($q) use ($activeIngredients) {
                            foreach ($activeIngredients as $ai) {
                                $q->orWhere('active_ingredient', 'like', "%{$ai}%");
                            }
                        })
                        ->select('drugs.id', 'drugs.name_ar', 'drugs.name_en',
                                 'drugs.active_ingredient', 'drugs.company',
                                 'drugs.dosage_form', 'drugs.concentration',
                                 'drugs.new_price', 'drugs.old_price')
                        ->limit(20)
                        ->get();

                    if ($alternatives->count() > 0) {
                        // قارن مع مخزون المستخدم
                        $myStockIds = DB::table('user_drug_inventory')
                            ->where('user_id', $userId)
                            ->where('branch_id', \App\Support\ActiveBranch::id())
                            ->where('quantity', '>', 0)
                            ->pluck('drug_id')
                            ->toArray();

                        $lines[] = "\n=== بدائل من قاعدة البيانات الكاملة ===";
                        $lines[] = "المواد الفعالة المطابقة: " . implode(' / ', array_map([$this, 'cleanText'], $activeIngredients));

                        foreach ($alternatives as $alt) {
                            $name      = $this->cleanText($alt->name_ar ?: $alt->name_en);
                            $active    = $this->cleanText($alt->active_ingredient ?? '');
                            $company   = $this->cleanText($alt->company ?? '');
                            $form      = $this->cleanText($alt->dosage_form ?? '');
                            $conc      = $this->cleanText($alt->concentration ?? '');
                            $price     = $alt->new_price ?? $alt->old_price ?? 0;
                            $inStock   = in_array($alt->id, $myStockIds) ? '✅ موجود في مخزونك' : '❌ غير موجود في مخزونك';
                            $lines[]   = "• {$name} | {$active} | {$form} {$conc} | {$company} | سعر: {$price} ج | {$inStock}";
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // تجاهل الخطأ بهدوء
        }

        // ─────────────────────────────────────────────
        // 4. المنتجات المنخفضة
        // ─────────────────────────────────────────────
        try {
            $lowStock = DB::table('drugs')
                ->join('user_drug_inventory as inv', function ($j) use ($userId) {
                    $j->on('inv.drug_id', '=', 'drugs.id')
                      ->where('inv.user_id', '=', $userId)
                      ->where('inv.branch_id', '=', \App\Support\ActiveBranch::id());
                })
                ->whereRaw('inv.quantity > 0 AND inv.quantity <= COALESCE(inv.min_quantity, 5)')
                ->select(
                    'drugs.name_ar', 'drugs.name_en', 'drugs.category',
                    'inv.quantity',
                    DB::raw('COALESCE(inv.min_quantity,5) as min_qty')
                )
                ->orderBy('inv.quantity')
                ->limit(20)
                ->get();

            if ($lowStock->count() > 0) {
                $lines[] = "\n=== منتجات منخفضة الكمية ===";
                foreach ($lowStock as $p) {
                    $name    = $this->cleanText($p->name_ar ?: $p->name_en);
                    $cat     = $p->category ? " [" . $this->cleanText($p->category) . "]" : '';
                    $lines[] = "- {$name}{$cat}: متاح {$p->quantity} علبة (الحد الأدنى: {$p->min_qty})";
                }
            } else {
                $lines[] = "\n=== منتجات منخفضة الكمية: لا يوجد حالياً ===";
            }
        } catch (\Exception $e) {}

        // ─────────────────────────────────────────────
        // 5. أدوية تنتهي خلال 90 يوم
        // ─────────────────────────────────────────────
        try {
            $expiring = DB::table('drugs')
                ->join('user_drug_inventory as inv', function ($j) use ($userId) {
                    $j->on('inv.drug_id', '=', 'drugs.id')
                      ->where('inv.user_id', '=', $userId)
                      ->where('inv.branch_id', '=', \App\Support\ActiveBranch::id());
                })
                ->whereNotNull('inv.expiry_date')
                ->whereDate('inv.expiry_date', '>=', Carbon::today())
                ->whereDate('inv.expiry_date', '<=', Carbon::today()->addDays(90))
                ->where('inv.quantity', '>', 0)
                ->select('drugs.name_ar', 'drugs.name_en', 'inv.quantity', 'inv.expiry_date', 'drugs.category')
                ->orderBy('inv.expiry_date')
                ->limit(15)
                ->get();

            if ($expiring->count() > 0) {
                $lines[] = "\n=== أدوية تنتهي صلاحيتها خلال 90 يوماً ===";
                foreach ($expiring as $p) {
                    $name    = $this->cleanText($p->name_ar ?: $p->name_en);
                    $days    = Carbon::today()->diffInDays(Carbon::parse($p->expiry_date));
                    $lines[] = "⚠️ {$name}: كمية {$p->quantity} علبة | ينتهي: {$p->expiry_date} (بعد {$days} يوم)";
                }
            } else {
                $lines[] = "\n=== لا توجد أدوية تنتهي صلاحيتها خلال 90 يوماً ===";
            }
        } catch (\Exception $e) {}

        // ─────────────────────────────────────────────
        // 6. إحصائيات المبيعات
        // ─────────────────────────────────────────────
        try {
            $todaySales = DB::table('sales')
                ->where('user_id', $userId)
                ->whereDate('created_at', Carbon::today())
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total),0) as total, COALESCE(SUM(discount),0) as discount')
                ->first();

            $monthSales = DB::table('sales')
                ->where('user_id', $userId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at',  Carbon::now()->year)
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total),0) as total')
                ->first();

            $lines[] = "\n=== إحصائيات المبيعات ===";
            $lines[] = "اليوم: {$todaySales->count} فاتورة | إجمالي: {$todaySales->total} ج | خصومات: {$todaySales->discount} ج";
            $lines[] = "هذا الشهر: {$monthSales->count} فاتورة | إجمالي: {$monthSales->total} ج";
        } catch (\Exception $e) {}

        // ─────────────────────────────────────────────
        // 7. آخر فواتير المبيعات
        // ─────────────────────────────────────────────
        try {
            $sales = DB::table('sales')
                ->where('sales.user_id', $userId)
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->orderByDesc('sales.created_at')
                ->limit(8)
                ->select('sales.id', 'sales.total', 'sales.discount', 'sales.payment_method', 'sales.created_at', 'customers.name as customer_name')
                ->get();

            if ($sales->count() > 0) {
                $lines[] = "\n=== آخر فواتير المبيعات ===";
                foreach ($sales as $s) {
                    $customer = $this->cleanText($s->customer_name ?? 'عميل عادي');
                    $date     = Carbon::parse($s->created_at)->format('Y-m-d H:i');
                    $pay      = $s->payment_method ?? '';
                    $lines[]  = "فاتورة #{$s->id} | {$customer} | {$s->total} ج | {$pay} | {$date}";
                }
            }
        } catch (\Exception $e) {}

        // ─────────────────────────────────────────────
        // 8. آخر فواتير الشراء
        // ─────────────────────────────────────────────
        try {
            $purchases = DB::table('purchase_invoices')
                ->where('purchase_invoices.user_id', $userId)
                ->leftJoin('suppliers', 'purchase_invoices.supplier_id', '=', 'suppliers.id')
                ->orderByDesc('purchase_invoices.created_at')
                ->limit(8)
                ->select(
                    'purchase_invoices.id', 'purchase_invoices.net_total',
                    'purchase_invoices.paid', 'purchase_invoices.remaining',
                    'purchase_invoices.payment_status', 'purchase_invoices.created_at',
                    'suppliers.name as supplier_name'
                )
                ->get();

            if ($purchases->count() > 0) {
                $lines[] = "\n=== آخر فواتير الشراء ===";
                foreach ($purchases as $p) {
                    $supplier = $this->cleanText($p->supplier_name ?? 'مورد غير محدد');
                    $date     = Carbon::parse($p->created_at)->format('Y-m-d H:i');
                    $status   = $p->payment_status === 'paid' ? 'مدفوعة' : "متبقي: {$p->remaining} ج";
                    $lines[]  = "فاتورة #{$p->id} | {$supplier} | {$p->net_total} ج | {$status} | {$date}";
                }
            }
        } catch (\Exception $e) {}

        // ─────────────────────────────────────────────
        // 9. أعلى الأدوية مبيعاً هذا الشهر
        // ─────────────────────────────────────────────
        try {
            $topSelling = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('drugs', 'sale_items.drug_id', '=', 'drugs.id')
                ->where('sales.user_id', $userId)
                ->whereMonth('sales.created_at', Carbon::now()->month)
                ->whereYear('sales.created_at',  Carbon::now()->year)
                ->selectRaw("
                    COALESCE(drugs.name_ar, drugs.name_en) as name,
                    SUM(sale_items.quantity) as total_qty,
                    SUM(sale_items.subtotal) as total_rev
                ")
                ->groupBy('drugs.id', 'drugs.name_ar', 'drugs.name_en')
                ->orderByDesc('total_rev')
                ->limit(10)
                ->get();

            if ($topSelling->count() > 0) {
                $lines[] = "\n=== أعلى الأدوية مبيعاً هذا الشهر ===";
                foreach ($topSelling as $t) {
                    $name    = $this->cleanText($t->name);
                    $lines[] = "• {$name}: {$t->total_qty} وحدة | إيراد: {$t->total_rev} ج";
                }
            }
        } catch (\Exception $e) {}

        // ─────────────────────────────────────────────
        // 10. موردون بديون
        // ─────────────────────────────────────────────
        try {
            $debtSuppliers = DB::table('suppliers')
                ->where('user_id', $userId)
                ->where('balance', '>', 0)
                ->orderByDesc('balance')
                ->limit(5)
                ->get(['name', 'balance']);

            if ($debtSuppliers->count() > 0) {
                $lines[] = "\n=== موردون بديون مستحقة ===";
                foreach ($debtSuppliers as $s) {
                    $name    = $this->cleanText($s->name);
                    $lines[] = "- {$name}: {$s->balance} ج";
                }
            }
        } catch (\Exception $e) {}

        // ✅ تنظيف النص الكامل من أي UTF-8 غير صالح
        $result = implode("\n", $lines);
        return $this->cleanText($result);
    }
}