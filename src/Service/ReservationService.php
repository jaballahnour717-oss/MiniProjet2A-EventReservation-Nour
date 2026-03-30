<?php

namespace App\Service;

use App\Entity\Reservation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class ReservationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment    $twig,
    ) {}

    public function sendConfirmationEmail(Reservation $reservation): void
    {
        try {
            $html = $this->twig->render('emails/reservation_confirm.html.twig', [
                'reservation' => $reservation,
                'event'       => $reservation->getEvent(),
            ]);

            $email = (new Email())
                ->from('noreply@eventbook.com')
                ->to($reservation->getEmail())
                ->subject('Confirmation de réservation — ' . $reservation->getEvent()?->getTitle())
                ->html($html);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // Log l'erreur sans bloquer la réservation
            // En production : utiliser un logger
            error_log('Erreur envoi email : ' . $e->getMessage());
        }
    }
}