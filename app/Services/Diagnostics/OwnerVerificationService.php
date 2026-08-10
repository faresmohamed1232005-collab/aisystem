<?php

namespace App\Services\Diagnostics;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OwnerVerificationService
{
    /** @return array{branch_id:string,owner_uuid:string} */
    public function verify(string $serverUrl, string $token, string $branchId, string $login, string $password): array
    {
        try {
            $response = Http::withHeaders(['X-Sync-Token' => $token])
                ->timeout(30)
                ->acceptJson()
                ->post(rtrim($serverUrl, '/').'/api/sync/verify-owner', [
                    'branch_id' => $branchId,
                    'owner_login' => $login,
                    'owner_password' => $password,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('تعذّر الاتصال بالسيرفر المركزي. تأكد من الإنترنت وعنوان السيرفر.', 0, $e);
        }

        if (! $response->successful() || ! $response->json('success')) {
            $message = match ($response->status()) {
                401 => 'بيانات مالك الصيدلية أو مفتاح المزامنة غير صحيحة.',
                403 => 'هذا الحساب ليس مالك الفرع المسجّل على هذا الجهاز.',
                404 => 'الفرع المسجّل غير موجود على السيرفر المركزي.',
                default => 'تعذّر التحقق من هوية مالك الفرع على السيرفر.',
            };

            throw new RuntimeException($message);
        }

        $verifiedBranch = (string) $response->json('branch_id');
        $ownerUuid = (string) $response->json('owner_uuid');
        if ($verifiedBranch === '' || $ownerUuid === '' || ! hash_equals($branchId, $verifiedBranch)) {
            throw new RuntimeException('أعاد السيرفر هوية فرع غير مطابقة؛ أُوقفت العملية لحماية البيانات.');
        }

        return ['branch_id' => $verifiedBranch, 'owner_uuid' => $ownerUuid];
    }
}
