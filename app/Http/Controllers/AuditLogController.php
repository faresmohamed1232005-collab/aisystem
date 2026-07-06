<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * عارض سجل التدقيق (Phase 3B) — قراءة فقط، لا تعديل/حذف (السجل غير قابل للتعديل).
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $event = $request->get('event', '');
        $type  = $request->get('type', '');

        $logs = AuditLog::where('user_id', Auth::id())
            ->when($event, fn($q) => $q->where('event', $event))
            ->when($type, fn($q) => $q->where('auditable_type', 'like', '%\\\\' . $type))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        // أنواع الموديلات الظاهرة (لفلتر مختصر).
        $types = AuditLog::where('user_id', Auth::id())
            ->select('auditable_type')->distinct()->pluck('auditable_type')
            ->map(fn($t) => class_basename($t))->unique()->values();

        return view('audit-logs.index', compact('logs', 'event', 'type', 'types'));
    }
}
