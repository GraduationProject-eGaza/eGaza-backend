<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\ServiceRequest;

class RequestAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $serviceRequest;

    public function __construct(ServiceRequest $serviceRequest)
    {
        $this->serviceRequest = $serviceRequest;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Request Assignment',
            'message' => "You have been assigned to handle request #{$this->serviceRequest->service_number} {$this->serviceRequest->serviceType->name}",
            'service_request_id' => $this->serviceRequest->id,
            'type' => 'request-assignment',
        ];
    }
}
