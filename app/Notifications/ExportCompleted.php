<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ExportCompleted extends Notification
{
    use Queueable;

    protected $filename;
    protected $originalFilename;

    /**
     * Create a new notification instance.
     */
    public function __construct($filename, $originalFilename)
    {
        $this->filename = $filename;
        $this->originalFilename = $originalFilename;
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
                    ->subject('Export Schedule Summary Selesai')
                    ->greeting('Halo ' . $notifiable->name . '!')
                    ->line('Export schedule summary yang Anda minta telah selesai diproses.')
                    ->line('File: ' . $this->originalFilename . '.xlsx')
                    ->line('Ukuran file: ' . $this->formatBytes(Storage::disk('local')->size($this->filename)))
                    ->action('Download File', route('schedules.export.download', ['filename' => basename($this->filename, '.xlsx')]))
                    ->line('File akan tersedia untuk diunduh selama 7 hari.')
                    ->line('Terima kasih telah menggunakan sistem kami!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'export_completed',
            'title' => 'Export Schedule Summary Selesai',
            'message' => 'Export schedule summary telah selesai diproses dan siap diunduh.',
            'filename' => $this->filename,
            'original_filename' => $this->originalFilename,
            'file_size' => Storage::disk('local')->size($this->filename),
            'download_url' => route('schedules.export.download', ['filename' => basename($this->filename, '.xlsx')])
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
