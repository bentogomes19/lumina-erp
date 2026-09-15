<?php

namespace App\Notifications;

use App\Support\SystemBranding;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FirstAccessInvitation extends Notification {

    /**
     * Cria a notificação de convite com o link descartável e sua validade.
     *
     * @param string $url
     * @param int $expiresInMinutes
     */
    public function __construct(
        private readonly string $url,
        private readonly int $expiresInMinutes,
    ) {
    }

    /**
     * Define os canais usados para entregar o convite.
     *
     * @param object $notifiable
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array {
        return ['mail'];
    }

    /**
     * Monta o e-mail que permite ao novo usuário definir a própria senha.
     *
     * @param object $notifiable
     *
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage {
        return (new MailMessage())
            ->subject('Seu primeiro acesso ao '.app(SystemBranding::class)->institutionName())
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Sua conta foi criada. Use o botão abaixo para definir sua senha de acesso.')
            ->action('Definir minha senha', $this->url)
            ->line("Este convite expira em {$this->expiresInMinutes} minutos e pode ser usado apenas uma vez.")
            ->line('Se você não esperava este convite, entre em contato com a administração.');
    }
}
