<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\User;

class CitizenFollowedInstitute extends Notification
{

    use Queueable;

    public $citizen;

    public function __construct($citizen) {
        $this->citizen = $citizen;
    }

    public function via($notifiable) {
        return ['database'];
    }

    public function toDatabase($notifiable) {
        return [
            'title' => 'New Follower',
            'message' => $this->citizen->full_name . ' followed your institution.',
            'citizen_id' => $this->citizen->id,
        ];
    }
}
