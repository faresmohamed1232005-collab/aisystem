<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * ChatGateway — نقطة موحّدة لنداء OpenAI Chat Completions.
 *
 * القرار حسب البيئة:
 *   - لو فيه مفتاح OpenAI محلي (config services.openai.key) → نداء مباشر (سلوك الويب كما هو).
 *   - وإلا لو الفرع مضبوط على سيرفر مزامنة (sync.server_url + sync.token) → نوجّه الطلب
 *     لـ {server}/api/ai/chat عبر السيرفر المركزي الذي يملك المفتاح. هكذا لا يُوزَّع
 *     مفتاح OpenAI داخل حزمة الديسكتوب (يبقى على السيرفر فقط).
 *   - وإلا → خطأ واضح.
 *
 * يرجّع دائماً: ['ok'=>bool, 'json'=>array (رد OpenAI الخام), 'status'=>int, 'error'=>?string].
 */
class ChatGateway
{
    public function chatCompletion(array $payload, int $timeout = 60): array
    {
        $localKey = (string) config('services.openai.key');

        if ($localKey !== '') {
            return $this->send(fn () => Http::withHeaders([
                'Authorization' => 'Bearer ' . $localKey,
                'Content-Type'  => 'application/json',
            ])->timeout($timeout)->post('https://api.openai.com/v1/chat/completions', $payload));
        }

        $serverUrl = rtrim((string) config('sync.server_url'), '/');
        $token     = (string) config('sync.token');

        if ($serverUrl !== '' && $token !== '') {
            return $this->send(fn () => Http::withHeaders(['X-Sync-Token' => $token])
                ->acceptJson()
                ->timeout($timeout + 15) // هامش لتحويل السيرفر إلى OpenAI
                ->post($serverUrl . '/api/ai/chat', $payload));
        }

        return [
            'ok'     => false,
            'json'   => [],
            'status' => 503,
            'error'  => 'خدمة الذكاء الاصطناعي غير متاحة حالياً (لا مفتاح محلي ولا سيرفر مزامنة مضبوط).',
        ];
    }

    /** @param callable():\Illuminate\Http\Client\Response $request */
    private function send(callable $request): array
    {
        try {
            $response = $request();
        } catch (\Throwable $e) {
            return [
                'ok'     => false,
                'json'   => [],
                'status' => 0,
                'error'  => 'تعذّر الاتصال بخدمة الذكاء الاصطناعي: ' . $e->getMessage(),
            ];
        }

        $json = [];
        try {
            $json = $response->json() ?? [];
        } catch (\Throwable) {
            $json = [];
        }

        if ($response->successful() && ! isset($json['error'])) {
            return ['ok' => true, 'json' => $json, 'status' => $response->status(), 'error' => null];
        }

        return [
            'ok'     => false,
            'json'   => $json,
            'status' => $response->status(),
            'error'  => data_get($json, 'error.message', 'تعذّر الاتصال بخدمة الذكاء الاصطناعي.'),
        ];
    }
}
