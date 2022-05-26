<?php

namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class Mailer
{
    protected $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    public function sendSignInEmail(string $mail)
    {
        $email = (new TemplatedEmail())
            ->from('agendigo@contact.com')
            ->to($mail)
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Inscription validée')
            ->text('Votre inscription sur Agendigo a bien été validée')
            ->htmlTemplate('emails/signin.html.twig');

            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                "une erreur est survenue";
            }
    }

    public function sendAppointmentEmail(string $mail, array $details=null, $date=null, $hour=null)
    {
        $email = (new TemplatedEmail())
            ->from('agendigo@contact.com')
            ->to($mail)
            ->subject('Rendez-vous enregistré')
            ->text('Rendez-vous enregistré')
            //on envoi un template twig
            ->htmlTemplate('emails/appointment.html.twig')
            // et on lui passe les données du rdv dans un tableau 
            ->context([
                'hour'    => $hour,
                'date'    => $date,
                'details' => $details
            ]);

            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                "une erreur est survenue";
            }
    }

    public function sendDeletedAppointmentEmail(string $mail)
    {
        $email = (new TemplatedEmail())
            ->from('agendigo@contact.com')
            ->to($mail)
            ->subject('Rendez-vous supprimé')
            ->text('Rendez-vous supprimé')
            ->htmlTemplate('emails/deleted.html.twig');

            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                "une erreur est survenue";
            }
    }
}