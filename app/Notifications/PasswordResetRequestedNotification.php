<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke admin saat pengguna meminta reset kata sandi dari halaman masuk.
 * Admin menindaklanjutinya dengan mengisi kata sandi baru di Manajemen Pengguna.
 */
class PasswordResetRequestedNotification extends Notification
{
    public function __construct(private readonly User $requester) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Permintaan Reset Kata Sandi',
            'message' => $this->requester->name.' ('.$this->requester->email.') lupa kata sandi dan meminta direset.',
            'requester_id' => $this->requester->id,
            'url' => '/admin/users?'.http_build_query(['role' => 'all', 'q' => $this->requester->email]),
        ]);
    }
}
