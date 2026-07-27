<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Emails a 6-digit verification code to a newly registered user. Sent
 * synchronously (not queued) so the code arrives while the user is waiting
 * on the verification screen.
 */
class EmailOtpNotification extends Notification
{
    public function __construct(
        public string $otp,
        public int $ttlMinutes = 10,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Email Anda')
            ->greeting('Halo ' . ($notifiable->name ?? '') . ',')
            ->line('Gunakan kode berikut untuk memverifikasi alamat email Anda:')
            ->line(new HtmlString(
                '<p style="font-size:32px;font-weight:700;letter-spacing:8px;margin:16px 0;text-align:center;">'
                . e($this->otp) .
                '</p>'
            ))
            ->line("Kode ini berlaku selama {$this->ttlMinutes} menit.")
            ->line('Abaikan email ini jika Anda tidak membuat akun.');
    }
}
