<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Bill;
use App\Models\User;

class BillPaidNotification extends Notification
{
    use Queueable;
public $bill;
    public $citizen;

    public function __construct(Bill $bill, User $citizen)
    {
        $this->bill = $bill;
        $this->citizen = $citizen;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Bill Paid',
            'message' => "{$this->citizen->full_name} has paid bill {$this->bill->bill_number} ({$this->bill->billType->name})",
            'bill_id' => $this->bill->id,
            'citizen_id' => $this->citizen->id,
            'type' => 'bill-paid',
        ];
    }}
