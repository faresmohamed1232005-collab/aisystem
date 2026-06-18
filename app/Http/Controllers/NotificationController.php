<?php
namespace App\Http\Controllers;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()->paginate(20);
        Notification::where('user_id', Auth::id())->update(['is_read' => true]);
        return view('notifications.index', compact('notifications'));
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => Notification::where('user_id', Auth::id())
                ->where('is_read', false)->count()
        ]);
    }
}
