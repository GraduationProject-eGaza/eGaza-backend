<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Bill;

class BillAssignedNotification extends Notification
{
    use Queueable;
use Queueable;

    public $bill;

    public function __construct(Bill $bill)
    {
        $this->bill = $bill;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Bill Assigned',
            'message' => "A new bill of type '{$this->bill->billType->name}' has been assigned to you. Due by {$this->bill->due_date}.",
            'bill_id' => $this->bill->id,
            'type' => 'bill-assignment'
        ];
    }
}
