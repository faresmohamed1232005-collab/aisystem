<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AiProxyController — وكيل OpenAI على السيرفر المركزي (master).
 *
 * الفروع (الديسكتوب) لا تحمل مفتاح OpenAI؛ تُرسل حمولة chat-completion هنا مع X-Sync-Token
 * (middleware sync.auth)، والسيرفر يمرّرها لـ OpenAI بمفتاحه ويعيد الرد كما هو. هكذا يبقى
 * المفتاح على السيرفر فقط ولا يُوزَّع داخل حزمة الـ .exe.
 */
class AiProxyController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $key = (string) config('services.openai.key');
        if ($key === '') {
            return response()->json(['error' => ['message' => 'مفتاح OpenAI غير مضبوط على السيرفر المركزي.']], 500);
        }

        $data = $request->validate([
            'model'            => 'sometimes|string|max:64',
            'messages'         => 'required|array|min:1',
            'max_tokens'       => 'sometimes|integer|min:1|max:16000',
            'temperature'      => 'sometimes|numeric|min:0|max:2',
            'top_p'            => 'sometimes|numeric|min:0|max:1',
            'response_format'  => 'sometimes|array',
            'presence_penalty' => 'sometimes|numeric|min:-2|max:2',
            'frequency_penalty'=> 'sometimes|numeric|min:-2|max:2',
        ]);

        // نمرّر فقط المفاتيح المسموح بها (لا passthrough عشوائي)، مع نموذج افتراضي آمن.
        $payload = array_merge(['model' => config('services.openai.model', 'gpt-4o-mini')], $data);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ])->timeout(180)->post('https://api.openai.com/v1/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::warning('AI proxy request failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => ['message' => 'تعذّر اتصال السيرفر بخدمة OpenAI.']], 502);
        }

        return response()->json($response->json() ?? [], $response->status());
    }
}
