<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ComplaintStatusChangedNotification extends Notification
{
    protected $complaint;

    public function __construct($complaint)
    {
        $this->complaint = $complaint;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Complaint Status Updated',
            'message' => "Your complaint {$this->complaint->title} status changed to: " . ucfirst($this->complaint->status),
            'complaint_id' => $this->complaint->id,
            'type' => 'complaint_status_updated',
        ];
    }
}
