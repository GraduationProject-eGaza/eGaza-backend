<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\ServiceRequest;

class NewServiceRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $serviceRequest;
    public $citizen;


    public function __construct(ServiceRequest $serviceRequest,$citizen)
    {
        $this->serviceRequest = $serviceRequest;
            $this->citizen = $citizen;

    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Service Request Received',
            'message' => "A new service request has been submitted by citizen{$this->citizen->full_name }",
            'service_request_id' => $this->serviceRequest->id,
            'type' => 'citizen-request',
        ];
    }
}
