<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ComplaintSubmittedNotification extends Notification
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
            'title' => 'New Complaint Submitted',
            'message' => "{$this->complaint->citizen->full_name} submitted a new complaint: {$this->complaint->title}",
            'complaint_id' => $this->complaint->id,
            'type' => 'complaint_submitted',
        ];
    }
}
