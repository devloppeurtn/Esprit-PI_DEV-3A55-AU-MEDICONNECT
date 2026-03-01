<?php

namespace App\Service;

use App\Entity\RendezVous;
use App\Entity\StatutRendezVous;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class RendezVousNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
    ) {
    }

    public function notifyPatientStatusUpdate(RendezVous $rdv, StatutRendezVous $status, string $source = 'secretariat'): void
    {
        $patient = $rdv->getPatient();
        $medecin = $rdv->getMedecin();
        if ($patient === null || $medecin === null || !$patient->getEmail()) {
            return;
        }

        $subject = match ($status) {
            StatutRendezVous::CONFIRME => 'Rendez-vous confirme',
            StatutRendezVous::ANNULE => 'Rendez-vous annule',
            StatutRendezVous::TERMINE => 'Rendez-vous termine',
            default => 'Mise a jour de votre rendez-vous',
        };

        $statusLabel = match ($status) {
            StatutRendezVous::CONFIRME => 'CONFIRME',
            StatutRendezVous::ANNULE => 'ANNULE',
            StatutRendezVous::TERMINE => 'TERMINE',
            default => 'EN ATTENTE',
        };

        $this->createInAppNotification(
            $patient,
            $subject,
            sprintf(
                'Rendez-vous avec Dr %s (%s) le %s : %s.',
                $this->escape((string) $medecin->getNomComplet()),
                $this->escape((string) ($medecin->getSpecialite() ?? 'Medecin')),
                $this->escape($rdv->getDateDebut()?->format('d/m/Y H:i') ?? '-'),
                $statusLabel
            ),
            $status === StatutRendezVous::CONFIRME ? 'success' : ($status === StatutRendezVous::ANNULE ? 'danger' : 'info')
        );

        $this->sendEmail(
            $patient->getEmail(),
            $subject,
            sprintf(
                '<p>Bonjour %s,</p><p>Votre rendez-vous avec Dr %s (%s) du <strong>%s</strong> est maintenant: <strong>%s</strong>.</p><p>Source: %s</p>',
                $this->escape((string) $patient->getNomComplet()),
                $this->escape((string) $medecin->getNomComplet()),
                $this->escape((string) ($medecin->getSpecialite() ?? 'Medecin')),
                $this->escape($rdv->getDateDebut()?->format('d/m/Y H:i') ?? '-'),
                $statusLabel,
                $this->escape($source)
            )
        );
    }

    public function notifyMedecinOnPatientCancellation(RendezVous $rdv): void
    {
        $patient = $rdv->getPatient();
        $medecin = $rdv->getMedecin();
        if ($patient === null || $medecin === null || !$medecin->getEmail()) {
            return;
        }

        $this->sendEmail(
            $medecin->getEmail(),
            'Annulation de rendez-vous par le patient',
            sprintf(
                '<p>Bonjour Dr %s,</p><p>Le patient <strong>%s</strong> a annule le rendez-vous prevu le <strong>%s</strong>.</p>',
                $this->escape((string) $medecin->getNomComplet()),
                $this->escape((string) $patient->getNomComplet()),
                $this->escape($rdv->getDateDebut()?->format('d/m/Y H:i') ?? '-')
            )
        );
    }

    private function sendEmail(string $to, string $subject, string $html): void
    {
        $fromAddress = $_ENV['MAILER_FROM'] ?? getenv('MAILER_FROM') ?: 'MediConnect <noreply@mediconnect.com>';
        $email = (new Email())
            ->from(Address::create($fromAddress))
            ->to($to)
            ->subject($subject)
            ->html($html);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->warning('RendezVous notification send failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createInAppNotification(\App\Entity\Utilisateur $user, string $title, string $message, string $type = 'info'): void
    {
        $notification = (new Notification())
            ->setUtilisateur($user)
            ->setTitre($title)
            ->setMessage($message)
            ->setType($type)
            ->setEstLu(false);

        $this->em->persist($notification);
        $this->em->flush();
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
