<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Announcement;

class AnnouncementCreated extends Notification
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
            'type' => 'announcement_created',
            'message' => 'A new announcement was submitted by employee: ' . $this->announcement->employee->full_name,
            'announcement_id' => $this->announcement->id,
            'institute_id' => $this->announcement->institute_id,
        ];
    }
}
