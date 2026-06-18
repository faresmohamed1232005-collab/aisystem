<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class MarketPriceCheckController extends Controller
{
    const MAX_DRUGS_PER_RUN = 50;
    const HIGH_THRESHOLD    = 0.15; // 15%
    const LOW_THRESHOLD     = 0.15;

    public function run(Request $request)
    {
        // حماية بسيطة: مفتاح سري في الرابط
        $secret = config('services.cron.secret');
        if (empty($secret) || $request->query('secret') !== $secret) {
            abort(403);
        }

        $apiKey = config('services.openai.key');
        if (empty($apiKey)) {
            return response()->json(['error' => 'OpenAI key not configured'], 500);
        }

        // ════════════════════════════════════════════
        // أكثر 50 منتج مبيعاً فعلياً (آخر 30 يوم) لكل صيدلية
        // ════════════════════════════════════════════
        $userIds = DB::table('sales')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->distinct()
            ->pluck('user_id');

        $totalChecked = 0;
        $totalAlerts  = 0;

        foreach ($userIds as $userId) {

            $topDrugs = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('drugs', 'sale_items.drug_id', '=', 'drugs.id')
                ->leftJoin('user_drug_inventory as inv', function ($j) use ($userId) {
                    $j->on('inv.drug_id', '=', 'drugs.id')
                      ->where('inv.user_id', '=', $userId);
                })
                ->where('sales.user_id', $userId)
                ->where('sales.created_at', '>=', Carbon::now()->subDays(30))
                ->selectRaw('
                    drugs.id as drug_id,
                    COALESCE(drugs.name_ar, drugs.name_en) as name,
                    drugs.concentration, drugs.dosage_form, drugs.company,
                    drugs.new_price, drugs.old_price,
                    inv.custom_price,
                    SUM(sale_items.quantity) as total_sold
                ')
                ->groupBy(
                    'drugs.id', 'drugs.name_ar', 'drugs.name_en',
                    'drugs.concentration', 'drugs.dosage_form', 'drugs.company',
                    'drugs.new_price', 'drugs.old_price', 'inv.custom_price'
                )
                ->orderByDesc('total_sold')
                ->limit(self::MAX_DRUGS_PER_RUN)
                ->get();

            foreach ($topDrugs as $drug) {
                $totalChecked++;
                if ($this->checkOne($userId, $drug, $apiKey)) {
                    $totalAlerts++;
                }
                usleep(500000); // نصف ثانية بين كل استعلام
            }
        }

        return response()->json([
            'success' => true,
            'pharmacies_checked' => $userIds->count(),
            'checked' => $totalChecked,
            'alerts'  => $totalAlerts,
        ]);
    }

 private function checkOne(int $userId, $drug, string $apiKey): bool
{
    $name = $drug->name;
    if (empty($name)) return false;

    $myPrice = floatval(
        $drug->custom_price
        ?? $drug->new_price
        ?? $drug->old_price
        ?? 0
    );

    if ($myPrice <= 0) return false;

    $extra = trim(($drug->concentration ?? '') . ' ' . ($drug->dosage_form ?? '') . ' ' . ($drug->company ?? ''));

    $prompt = <<<PROMPT
أنت تعرف الأسعار التقريبية المتداولة في السوق المصري للأدوية بناءً على معرفتك حتى تاريخ آخر تدريب لك.

دواء اسمه: "{$name}" {$extra}.

السعر الحالي عندي في الصيدلية هو: {$myPrice} جنيه مصري (سعر العلبة).

قدّر متوسط السعر السوقي التقريبي بالجنيه المصري بناءً على معرفتك، مع مراعاة أن الأسعار في مصر تتغير بكثرة وقد تكون معلوماتك قديمة.

رد فقط بصيغة JSON بدون أي نص إضافي بهذا الشكل:
{"market_price": رقم, "status": "ok" أو "not_found", "note": "ملاحظة قصيرة توضح مدى ثقتك في التقدير"}

- إذا لم يكن الدواء معروفاً لديك أو لا تملك أي تقدير منطقي، اجعل status = "not_found" وmarket_price = 0.
PROMPT;

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(40)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => config('services.openai.model', 'gpt-4o-mini'),
            'messages'    => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens'  => 300,
            'temperature' => 0.3,
        ]);

        if (!$response->successful()) {
            return false;
        }

        $content = trim($response->json('choices.0.message.content', ''));
        $content = preg_replace('/```json|```/', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (!is_array($data) || ($data['status'] ?? '') !== 'ok') {
            return false;
        }

        $marketPrice = floatval($data['market_price'] ?? 0);
        if ($marketPrice <= 0) return false;

        $diffRatio = ($myPrice - $marketPrice) / $marketPrice;

        if ($diffRatio >= self::HIGH_THRESHOLD) {
            $percent = round($diffRatio * 100);
            Notification::create([
                'user_id' => $userId,
                'type'    => 'price_high',
                'title'   => '⚠️ سعر مرتفع عن التقدير السوقي',
                'message' => "سعر \"{$name}\" عندك ({$myPrice} ج) أعلى من التقدير السوقي التقريبي (~{$marketPrice} ج) بنسبة {$percent}%. (تقدير تقريبي وليس بحث حي، يُرجى المراجعة اليدوية).",
                'is_read' => false,
            ]);
            return true;
        }

        if ($diffRatio <= -self::LOW_THRESHOLD) {
            $percent = round(abs($diffRatio) * 100);
            Notification::create([
                'user_id' => $userId,
                'type'    => 'price_low',
                'title'   => '💰 سعر منخفض عن التقدير السوقي',
                'message' => "سعر \"{$name}\" عندك ({$myPrice} ج) أقل من التقدير السوقي التقريبي (~{$marketPrice} ج) بنسبة {$percent}%. (تقدير تقريبي وليس بحث حي، يُرجى المراجعة اليدوية).",
                'is_read' => false,
            ]);
            return true;
        }

        return false;

    } catch (\Exception $e) {
        return false;
    }
}
}