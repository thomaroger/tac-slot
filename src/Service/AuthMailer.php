<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Adherent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class AuthMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail,
    ) {
    }

    public function sendLoginCode(Adherent $adherent, string $code): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($adherent->getEmail() ?? '')
            ->subject('Votre code de connexion')
            ->htmlTemplate('emails/login_code.html.twig')
            ->context([
                'adherent' => $adherent,
                'code' => $code,
            ]);

        $this->mailer->send($email);
    }

    public function sendFirstLoginConfirmation(Adherent $adherent, string $token): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($adherent->getEmail() ?? '')
            ->subject('Validation de votre compte')
            ->htmlTemplate('emails/confirm_account.html.twig')
            ->context([
                'adherent' => $adherent,
                'token' => $token,
            ]);

        $this->mailer->send($email);
    }
}
