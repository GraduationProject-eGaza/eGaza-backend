<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FriendRequestAccepted extends Notification
{
    use Queueable;

    public $receiver;
        // protected $status;


    public function __construct($receiver) {
        $this->receiver = $receiver;
                // $this->status = $status;

    }

    public function via($notifiable) {
        return ['database'];
    }

    public function toDatabase($notifiable) {
        return [
            'title' => 'Friend Request Accepted',
            'message' => $this->receiver->full_name . ' accepted your friend request.',
            'receiver_id' => $this->receiver->id,
        ];
    }
}
