<?php

namespace App\Notifications;

use App\Support\SystemBranding;
use Illuminate\Auth\Notifications\ResetPassword as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordReset extends BaseNotification {

    /**
     * URL do formulário de redefinição gerada pelo painel Filament.
     */
    public string $url;

    /**
     * Monta o e-mail de recuperação sem depender do processamento de filas.
     *
     * @param object $notifiable
     *
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage {
        $expires = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage())
            ->subject('Recuperação de senha do '.app(SystemBranding::class)->institutionName())
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta.')
            ->action('Redefinir minha senha', $this->resetUrl($notifiable))
            ->line("Este link expira em {$expires} minutos e pode ser usado apenas uma vez.")
            ->line('Se você não solicitou a alteração, ignore esta mensagem.');
    }

    /**
     * Retorna a URL de recuperação preparada pelo painel.
     *
     * @param object $notifiable
     *
     * @return string
     */
    protected function resetUrl($notifiable): string {
        return $this->url;
    }
}
