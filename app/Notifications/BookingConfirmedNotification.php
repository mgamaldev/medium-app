<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly Booking $booking)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject('Booking Confirmed')
            ->greeting('Dear '.$notifiable->name)
            ->line('Your booking has been confirmed.')
            ->line("Booking ID: #{$this->booking->id}")
            ->line("Slot Date and Time: {$this->booking->slot->starts_at}")
            ->action('View Booking Details', url("/bookings/{$this->booking->id}"))
            ->line('Thank you for using our service');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'slot_id' => $this->booking->slot_id,
            'status' => $this->booking->status,
        ];
    }
}
