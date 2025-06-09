<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FriendRequestSent extends Notification
{
    use Queueable;

    public $sender;

    public function __construct($sender) {
        $this->sender = $sender;
    }

    public function via($notifiable) {
        return ['database'];
    }

public function toDatabase($notifiable)
{
    return [
        'title' => 'New Friend Request',
        'message' => $this->sender->full_name . ' sent you a friend request',
        'sender_id' => $this->sender->id
    ];
}

}
