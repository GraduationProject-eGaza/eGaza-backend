<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\ServiceRequest;

class RequestStatusUpdatedNotification extends Notification implements ShouldQueue
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
            'title' => 'Request Status Updated',
            'message' => "Your request {$this->serviceRequest->serviceType->name} has been {$this->serviceRequest->status}.",
            'service_request_id' => $this->serviceRequest->id,
            'type' => 'status-update',
        ];
    }
}
