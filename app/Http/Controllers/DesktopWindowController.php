<?php

namespace App\Http\Controllers;

use App\Support\DesktopWindows;
use App\Support\Runtime;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Native\Desktop\Facades\Window;

/**
 * DesktopWindowController — فتح شاشة في نافذة مستقلة على تطبيق سطح المكتب (NativePHP).
 *
 * يمكّن المستخدم من العمل على أكثر من شاشة في نفس الوقت (مثلاً فاتورة بيع مفتوحة + المخزون
 * في نافذة أخرى) دون مغادرة صفحته الحالية وفقدان بياناتها (سلة الفاتورة تعيش في ذاكرة
 * النافذة). كل النوافذ تشترك في الجلسة، فالنافذة الجديدة مسجّلة دخول تلقائياً بنفس المستخدم.
 */
class DesktopWindowController extends Controller
{
    public function open(Request $request)
    {
        // على الديسكتوب فقط — الويب له تبويبات المتصفح.
        abort_unless(Runtime::isDesktop(), 404);

        // الهدف يجب أن يكون عضواً في القائمة البيضاء: يضمن مساراً داخلياً موجوداً بلا params
        // ولا مسارات مدمّرة/خارجية (لا نمرّر url خام من المستخدم أبداً).
        $data = $request->validate([
            'target' => ['required', 'string', Rule::in(DesktopWindows::POPPABLE)],
        ]);

        // id فريد لكل نافذة — بدونه يستخدم NativePHP id='main' فيركّز/يستبدل النافذة الحالية.
        $id = 'win-' . Str::uuid();

        Window::open($id)
            ->route($data['target'])
            ->width(1280)->height(800)
            ->minWidth(1024)->minHeight(700)
            ->maximized();

        return response()->json(['ok' => true, 'id' => $id]);
    }
}
