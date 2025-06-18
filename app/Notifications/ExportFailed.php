<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportFailed extends Notification
{
    use Queueable;

    protected $filename;
    protected $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct($filename, $errorMessage)
    {
        $this->filename = $filename;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Export Schedule Summary Gagal')
                    ->greeting('Halo ' . $notifiable->name . '!')
                    ->line('Maaf, export schedule summary yang Anda minta mengalami kegagalan.')
                    ->line('File: ' . $this->filename . '.xlsx')
                    ->line('Error: ' . $this->errorMessage)
                    ->line('Silakan coba lagi dengan filter yang lebih spesifik atau hubungi administrator.')
                    ->action('Coba Lagi', route('schedules.export.summary.form'))
                    ->line('Terima kasih atas pengertian Anda.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'export_failed',
            'title' => 'Export Schedule Summary Gagal',
            'message' => 'Export schedule summary mengalami kegagalan: ' . $this->errorMessage,
            'filename' => $this->filename,
            'error_message' => $this->errorMessage,
            'retry_url' => route('schedules.export.summary.form')
        ];
    }
}
