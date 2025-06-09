<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Announcement;


class AnnouncementApproved extends Notification
{
     use Queueable;

    protected $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'announcement_approved',
            'message' => 'Your announcement "' . $this->announcement->title . '" has been approved.',
            'announcement_id' => $this->announcement->id,
        ];
    }
}
