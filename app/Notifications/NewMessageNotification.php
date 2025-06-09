<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Message;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Message Received',
            'message' => "You received a new message from {$this->message->sender->full_name}{$this->message->sender->institution_name}",
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'type' => 'new-message',
        ];
    }
}
