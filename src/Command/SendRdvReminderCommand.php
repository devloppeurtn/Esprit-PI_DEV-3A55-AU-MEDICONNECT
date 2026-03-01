<?php

namespace App\Command;

use App\Entity\Notification;
use App\Entity\Patient;
use App\Repository\NotificationRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rdv:send-reminders',
    description: 'Envoie des notifications de rappel 1h avant les rendez-vous confirmes.',
)]
class SendRdvReminderCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private RendezVousRepository $rendezVousRepository,
        private NotificationRepository $notificationRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'minutes-before',
                null,
                InputOption::VALUE_OPTIONAL,
                'Nombre de minutes avant le rendez-vous pour envoyer le rappel.',
                60
            )
            ->addOption(
                'window-minutes',
                null,
                InputOption::VALUE_OPTIONAL,
                'Fenetre de traitement en minutes (utile si la commande tourne en cron).',
                5
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $minutesBefore = max(1, (int) $input->getOption('minutes-before'));
        $windowMinutes = max(1, (int) $input->getOption('window-minutes'));

        $now = new \DateTimeImmutable();
        $target = $now->modify(sprintf('+%d minutes', $minutesBefore));

        $windowStart = $this->truncateToMinute($target->modify(sprintf('-%d minutes', $windowMinutes)));
        $windowEnd = $this->truncateToMinute($target->modify(sprintf('+%d minutes', $windowMinutes + 1)));

        $rdvs = $this->rendezVousRepository->findConfirmedStartingBetween($windowStart, $windowEnd);

        $created = 0;
        $skipped = 0;

        foreach ($rdvs as $rdv) {
            $patient = $rdv->getPatient();
            if (!$patient instanceof Patient) {
                ++$skipped;
                continue;
            }

            $rdvId = $rdv->getId();
            if ($rdvId === null) {
                ++$skipped;
                continue;
            }

            $medecinName = $rdv->getMedecin()?->getNomComplet() ?? 'votre medecin';
            $dateText = $rdv->getDateDebut()?->format('d/m/Y H:i') ?? 'horaire non defini';
            $title = 'Rappel de consultation';
            $message = sprintf(
                'Rappel: votre rendez-vous avec %s est prevu le %s (%s).',
                $medecinName,
                $dateText,
                $this->formatApproxDelay($minutesBefore)
            );

            if ($this->notificationRepository->existsByUserTitleAndMessage($patient, $title, $message)) {
                ++$skipped;
                continue;
            }

            $notification = (new Notification())
                ->setUtilisateur($patient)
                ->setTitre($title)
                ->setMessage($message)
                ->setType('warning')
                ->setEstLu(false);

            $this->em->persist($notification);
            ++$created;
        }

        if ($created > 0) {
            $this->em->flush();
        }

        $io->success(sprintf(
            'Traitement termine (%s -> %s). %d notification(s) creee(s), %d rendez-vous ignore(s).',
            $windowStart->format('d/m/Y H:i:s'),
            $windowEnd->format('d/m/Y H:i:s'),
            $created,
            $skipped
        ));

        return Command::SUCCESS;
    }

    private function truncateToMinute(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->setTime(
            (int) $date->format('H'),
            (int) $date->format('i'),
            0
        );
    }

    private function formatApproxDelay(int $minutesBefore): string
    {
        $minutesBefore = max(1, $minutesBefore);

        if ($minutesBefore < 60) {
            return sprintf('dans environ %d minute%s', $minutesBefore, $minutesBefore > 1 ? 's' : '');
        }

        $days = intdiv($minutesBefore, 1440);
        $remainingAfterDays = $minutesBefore % 1440;
        $hours = intdiv($remainingAfterDays, 60);
        $minutes = $remainingAfterDays % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = sprintf('%d jour%s', $days, $days > 1 ? 's' : '');
        }
        if ($hours > 0) {
            $parts[] = sprintf('%d heure%s', $hours, $hours > 1 ? 's' : '');
        }
        if ($minutes > 0 && $days === 0) {
            $parts[] = sprintf('%d minute%s', $minutes, $minutes > 1 ? 's' : '');
        }

        if (count($parts) === 0) {
            $parts[] = '1 heure';
        }

        return 'dans environ ' . implode(' ', $parts);
    }
}
