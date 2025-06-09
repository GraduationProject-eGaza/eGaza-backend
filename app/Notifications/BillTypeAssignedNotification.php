<?php

namespace App\Notifications;

use App\Models\BillType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillTypeAssignedNotification extends Notification
{
    use Queueable;
public $billType;

    public function __construct(BillType $billType)
    {
        $this->billType = $billType;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Assigned to Bill Type',
            'message' => "You have been assigned to bill type: {$this->billType->name}.",
            'bill_type_id' => $this->billType->id,
            'type' => 'bill-assignment',
        ];
    }
}
