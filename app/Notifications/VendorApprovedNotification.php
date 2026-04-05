<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Vendor $vendor) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Congratulations! Your Vendor Account is Approved')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Great news! Your vendor account "' . $this->vendor->business_name . '" has been approved.')
            ->line('You can now start listing products and selling on our marketplace.')
            ->action('Start Selling', url('/vendor-panel'))
            ->line('Welcome aboard!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'vendor_approved',
            'vendor_id' => $this->vendor->id,
            'business_name' => $this->vendor->business_name,
            'message' => 'Your vendor account "' . $this->vendor->business_name . '" has been approved. Start selling now!',
        ];
    }
}
