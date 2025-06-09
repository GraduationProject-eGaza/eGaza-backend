<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\ServiceType;

class ServiceTypeAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $serviceType;

    public function __construct(ServiceType $serviceType)
    {
        $this->serviceType = $serviceType;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Assigned to Service Type',
            'message' => "You have been assigned to service type: {$this->serviceType->name}.",
            'service_type_id' => $this->serviceType->id,
            'type' => 'service-assignment',
        ];
    }
}
