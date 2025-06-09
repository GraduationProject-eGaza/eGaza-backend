<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ComplaintAssignedNotification extends Notification
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
            'title' => 'Complaint Assigned to You',
            'message' => "You have been assigned to complaint #{$this->complaint->complaint_number}: {$this->complaint->title}",
            'complaint_id' => $this->complaint->id,
            'type' => 'complaint_assigned',
        ];
    }
}
