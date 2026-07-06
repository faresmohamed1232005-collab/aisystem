<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * app_settings — مخزن إعدادات دائم key/value (يُستخدم على الفرع أساساً).
 *
 * يحفظ هوية الفرع وإعدادات المزامنة المُدخَلة في «شاشة إعداد أول تشغيل»:
 *   branch.id, branch.code, sync.server_url, sync.token, sync.enabled, registered_at
 * بديلاً دائماً عن env المخبوز/الكاش المتقلّب (انظر App\Support\Settings).
 *
 * migration إضافي غير كاسر.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
