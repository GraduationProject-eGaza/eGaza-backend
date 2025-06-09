<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function citizenNotifications() {
        $user = Auth::user();
        if ($user->type !== 'citizen') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['notifications' => $user->notifications]);
    }

    public function instituteNotifications() {
        $user = Auth::user();
        if ($user->type !== 'government-institute') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['notifications' => $user->notifications]);
    }
        public function employeeNotifications()
    {
        $user = Auth::user();
        if ($user->type !== 'government-employee') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['notifications' => $user->notifications]);
    }

    public function markAsRead($id) {
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }
    public function unreadNotificationCount()
{
    $count = Auth::user()->unreadNotifications->count();

    return response()->json(['unread_notifications' => $count]);
}


}
