<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;


    public function __construct(
        public readonly Alert $alert
    ) {
    }


    /**
     * Notification channels
     */
    public function via(object $notifiable): array
    {
        return [
            'mail',
            'database',
        ];
    }


    /**
     * Email message
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)

            ->subject(
                'TMS Alert - ' . strtoupper($this->alert->severity)
            )

            ->greeting(
                'Dobrý den,'
            )

            ->line(
                $this->alert->message
            )

            ->line(
                'Trip ID: ' . $this->alert->trip_id
            )

            ->line(
                'Typ alertu: ' . $this->alert->type
            )

            ->line(
                'Priorita: ' . $this->alert->severity
            )

            ->line(
                'Čas vytvoření: ' . $this->alert->created_at
            )

            ->salutation(
                'TMS systém'
            );
    }


    /**
     * Database notification payload
     */
    public function toDatabase(object $notifiable): array
    {
        return [

            'alert_id' => $this->alert->id,

            'trip_id' => $this->alert->trip_id,

            'type' => $this->alert->type,

            'severity' => $this->alert->severity,

            'message' => $this->alert->message,

            'created_at' => now(),

        ];
    }


    /**
     * Array representation
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'trip_id' => $this->alert->trip_id,
            'type' => $this->alert->type,
            'severity' => $this->alert->severity,
            'message' => $this->alert->message,
        ];
    }
}