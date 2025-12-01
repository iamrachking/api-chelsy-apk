<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class ResetPasswordNotification extends BaseResetPasswordNotification
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Détecter si c'est un admin ou un utilisateur normal (API mobile)
        $isAdmin = $notifiable->is_admin ?? false;
        
        if ($isAdmin) {
            // Pour l'admin web : URL avec lien cliquable, pas de token affiché
            $url = $this->resetUrlForAdmin($notifiable);
            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe - CHELSY Restaurant')
                ->view('emails.reset-password', [
                    'user' => $notifiable,
                    'token' => null, // Pas de token pour l'admin (sécurité)
                    'resetUrl' => $url,
                ]);
        } else {
            // Pour l'API mobile : afficher le token car l'utilisateur doit l'entrer manuellement
            $url = $this->resetUrlForMobile($notifiable);
            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe - CHELSY Restaurant')
                ->view('emails.reset-password-mobile', [
                    'user' => $notifiable,
                    'token' => $this->token,
                    'resetUrl' => $url,
                ]);
        }
    }

    /**
     * Get the reset URL for admin web.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrlForAdmin($notifiable)
    {
        $email = $notifiable->getEmailForPasswordReset();
        return URL::route('admin.password.reset', [
            'token' => $this->token,
        ]) . '?email=' . urlencode($email);
    }

    /**
     * Get the reset URL for mobile API.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrlForMobile($notifiable)
    {
        // URL pour l'application mobile (si deep linking est configuré)
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost'));
        $email = $notifiable->getEmailForPasswordReset();
        return $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($email);
    }
}

