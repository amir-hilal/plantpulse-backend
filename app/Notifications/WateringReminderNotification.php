<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Plant;

class WateringReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $plant;

    /**
     * Create a new notification instance.
     *
     * @param Plant $plant
     */
    public function __construct(Plant $plant)
    {
        $this->plant = $plant;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Watering Reminder for ' . $this->plant->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('It’s time to water your plant: ' . $this->plant->name)
            ->line('Watering frequency: ' . $this->plant->watering_frequency . ' times per week.')
            ->line('Next watering time: ' . $this->plant->next_time_to_water->format('l, F j, Y'))
            ->action('View Your Plant', url('/gardens/' . $this->plant->garden_id))
            ->line('Thank you for using PlantPulse!');
    }
}
