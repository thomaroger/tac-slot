<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Adherent;
use App\Entity\Slot;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class ReservationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail,
    ) {
    }

    public function sendSlotCancelledMissingAirKey(Adherent $adherent, Slot $slot): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($adherent->getEmail() ?? '')
            ->subject('Annulation de votre réservation de créneau')
            ->htmlTemplate('emails/slot_cancelled_missing_airkey.html.twig')
            ->context([
                'adherent' => $adherent,
                'slot' => $slot,
            ]);

        $this->mailer->send($email);
    }
}
