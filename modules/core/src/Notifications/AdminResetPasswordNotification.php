<?php

namespace Pagify\Core\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $expireMinutes = (int) config('auth.passwords.admins.expire', 60);
        $mailUi = $this->mailUiConfig();

        return (new MailMessage)
            ->subject(__('core::messages.auth.password_reset.subject'))
            ->view('core::emails.admin-password-reset', [
                'appName' => (string) config('app.name', 'Pagify'),
                'resetUrl' => $resetUrl,
                'expireMinutes' => $expireMinutes,
                'greeting' => __('core::messages.auth.password_reset.greeting'),
                'intro' => __('core::messages.auth.password_reset.intro'),
                'ctaLabel' => __('core::messages.auth.password_reset.action'),
                'expiryText' => __('core::messages.auth.password_reset.expiry', ['count' => $expireMinutes]),
                'ignoreText' => __('core::messages.auth.password_reset.ignore'),
                'footerSignatureNote' => __('core::messages.mail.transactional.signature_note'),
                'footerContactText' => __('core::messages.mail.transactional.contact', [
                    'email' => (string) config('mail.from.address', 'support@example.com'),
                ]),
                'mailUi' => $mailUi,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mailUiConfig(): array
    {
        $theme = (string) config('core_mail_ui.theme', 'default');
        $defaultStyles = (array) config('core_mail_ui.themes.default', []);
        $themeStyles = (array) config("core_mail_ui.themes.{$theme}", []);

        $styles = array_replace($defaultStyles, $themeStyles);

        $overrideJson = (string) config('core_mail_ui.override_json', '');
        if ($overrideJson !== '') {
            $decoded = json_decode($overrideJson, true);
            if (is_array($decoded)) {
                $styles = array_replace($styles, $decoded);
            }
        }

        return [
            'theme' => $theme,
            'brand' => [
                'name' => (string) config('core_mail_ui.brand.name', config('app.name', 'Pagify')),
                'logo_url' => config('core_mail_ui.brand.logo_url'),
                'logo_alt' => (string) config('core_mail_ui.brand.logo_alt', config('app.name', 'Pagify')),
            ],
            'styles' => $styles,
        ];
    }

    protected function resetUrl($notifiable): string
    {
        return route('core.admin.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
