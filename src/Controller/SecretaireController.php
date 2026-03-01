<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Patient;
use App\Entity\PlanningMedecin;
use App\Entity\RendezVous;
use App\Entity\Secretaire;
use App\Entity\StatutRendezVous;
use App\Form\PlanningMedecinType;
use App\Repository\PlanningMedecinRepository;
use App\Repository\RendezVousRepository;
use App\Service\DisponibiliteService;
use App\Service\RendezVousBookingValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/secretaire')]
#[IsGranted('ROLE_SECRETAIRE')]
class SecretaireController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RendezVousRepository $rdvRepo,
        private PlanningMedecinRepository $planningRepo,
        private DisponibiliteService $dispoService,
        private RendezVousBookingValidator $rdvBookingValidator,
    ) {
    }

    #[Route('', name: 'app_secretaire_index', methods: ['GET'])]
    public function index(): Response
    {
        $s = $this->getUser();
        if (!$s instanceof Secretaire) {
            return $this->redirectToRoute('app_profile');
        }

        $m = $s->getMedecin();
        $invitations = $s->getInvitations();
        $planning = $m ? $m->getPlanning() : null;
        $rdvsAujourdhui = $m ? $this->rdvRepo->findByMedecinAndDate($m, new \DateTime()) : [];

        return $this->render('secretaire/index.html.twig', [
            'secretaire' => $s,
            'medecin' => $m,
            'planning' => $planning,
            'rdvsAujourdhui' => $rdvsAujourdhui,
            'invitationsEnAttente' => $invitations->filter(fn ($i) => $i->getStatut()->value === 'EN_ATTENTE'),
            'invitationsAcceptees' => $invitations->filter(fn ($i) => $i->getStatut()->value === 'ACCEPTEE'),
            'invitationsRefusees' => $invitations->filter(fn ($i) => $i->getStatut()->value === 'REFUSEE'),
        ]);
    }

    #[Route('/rendez-vous', name: 'app_secretaire_rendez_vous', methods: ['GET'])]
    public function rendezVous(): Response
    {
        $s = $this->getUser();
        if (!$s instanceof Secretaire) {
            return $this->redirectToRoute('app_profile');
        }

        $m = $s->getMedecin();
        if (!$m) {
            $this->addFlash('warning', 'Aucun medecin associe.');

            return $this->render('secretaire/rendez_vous.html.twig', [
                'secretaire' => $s,
                'rdvsEnAttente' => [],
                'rdvsConfirmes' => [],
            ]);
        }

        $rdvsEnAttente = $this->rdvRepo->findEnAttenteByMedecin($m);
        $rdvsConfirmes = array_filter(
            $this->rdvRepo->findByMedecin($m),
            static fn ($r) => $r->getStatut() === StatutRendezVous::CONFIRME
        );

        return $this->render('secretaire/rendez_vous.html.twig', [
            'secretaire' => $s,
            'rdvsEnAttente' => $rdvsEnAttente,
            'rdvsConfirmes' => $rdvsConfirmes,
        ]);
    }

    #[Route('/rendez-vous/{id}/valider', name: 'app_secretaire_rdv_valider', methods: ['POST'])]
    public function validerRdv(Request $request, RendezVous $rdv): Response
    {
        $s = $this->getUser();
        if (!$s instanceof Secretaire || $s->getMedecin() !== $rdv->getMedecin()) {
            return $this->redirectToRoute('app_secretaire_rendez_vous');
        }

        if ($this->isCsrfTokenValid('valider_rdv_' . $rdv->getId(), $request->request->get('_token'))) {
            if ($rdv->getStatut() === StatutRendezVous::CONFIRME) {
                $this->addFlash('info', 'Ce rendez-vous est deja confirme.');
                return $this->redirectToRoute('app_secretaire_rendez_vous');
            }

            $rdv->setStatut(StatutRendezVous::CONFIRME);
            $this->createRdvPatientNotification($rdv, true);
            $this->em->flush();
            $this->addFlash('success', 'Rendez-vous confirme.');
        }

        return $this->redirectToRoute('app_secretaire_rendez_vous');
    }

    #[Route('/rendez-vous/{id}/refuser', name: 'app_secretaire_rdv_refuser', methods: ['POST'])]
    public function refuserRdv(Request $request, RendezVous $rdv): Response
    {
        $s = $this->getUser();
        if (!$s instanceof Secretaire || $s->getMedecin() !== $rdv->getMedecin()) {
            return $this->redirectToRoute('app_secretaire_rendez_vous');
        }

        if ($this->isCsrfTokenValid('refuser_rdv_' . $rdv->getId(), $request->request->get('_token'))) {
            if ($rdv->getStatut() === StatutRendezVous::ANNULE) {
                $this->addFlash('info', 'Ce rendez-vous est deja refuse.');
                return $this->redirectToRoute('app_secretaire_rendez_vous');
            }

            $rdv->setStatut(StatutRendezVous::ANNULE);
            $this->createRdvPatientNotification($rdv, false);
            $this->em->flush();
            $this->addFlash('success', 'Rendez-vous refuse.');
        }

        return $this->redirectToRoute('app_secretaire_rendez_vous');
    }

    private function createRdvPatientNotification(RendezVous $rdv, bool $isConfirmed): void
    {
        $patient = $rdv->getPatient();
        if (!$patient) {
            return;
        }

        $medecinName = $rdv->getMedecin()?->getNomComplet() ?? 'votre medecin';
        $dateRdv = $rdv->getDateDebut()?->format('d/m/Y H:i') ?? 'date a confirmer';

        $notification = (new Notification())
            ->setUtilisateur($patient)
            ->setTitre($isConfirmed ? 'Rendez-vous confirme' : 'Rendez-vous refuse')
            ->setMessage(
                $isConfirmed
                    ? sprintf('Votre rendez-vous du %s avec %s a ete confirme par la secretaire.', $dateRdv, $medecinName)
                    : sprintf('Votre rendez-vous du %s avec %s a ete refuse par la secretaire.', $dateRdv, $medecinName)
            )
            ->setType($isConfirmed ? 'success' : 'danger')
            ->setEstLu(false);

        $this->em->persist($notification);
    }

    #[Route('/planning/configurer', name: 'app_secretaire_planning_config', methods: ['GET', 'POST'])]
    public function planningConfigurer(Request $request): Response
    {
        $s = $this->getUser();
        if (!$s instanceof Secretaire) {
            return $this->redirectToRoute('app_profile');
        }

        $m = $s->getMedecin();
        if (!$m) {
            return $this->redirectToRoute('app_secretaire_index');
        }

        $p = $m->getPlanning() ?: (new PlanningMedecin())->setMedecin($m);
        $form = $this->createForm(PlanningMedecinType::class, $p);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($p);
            $m->setPlanning($p);
            $this->em->flush();
            $this->addFlash('success', 'Planning mis a jour.');

            return $this->redirectToRoute('app_secretaire_agenda');
        }

        return $this->render('secretaire/planning_config.html.twig', [
            'form' => $form->createView(),
            'medecin' => $m,
            'planning' => $p,
            'secretaire' => $s,
        ]);
    }

    #[Route('/agenda', name: 'app_secretaire_agenda', methods: ['GET'])]
    public function agenda(Request $request): Response
    {
        $s = $this->getUser();
        if (!$s instanceof Secretaire) {
            return $this->redirectToRoute('app_profile');
        }

        $m = $s->getMedecin();
        if (!$m) {
            return $this->redirectToRoute('app_secretaire_index');
        }

        $dateStr = $request->query->get('date', date('Y-m-d'));
        try {
            $date = new \DateTime((string) $dateStr);
        } catch (\Exception) {
            $date = new \DateTime();
        }

        $monday = clone $date;
        if ($monday->format('N') !== '1') {
            $monday->modify('last monday');
        }
        $monday->setTime(0, 0, 0);

        $weekData = [];
        $p = $m->getPlanning();

        for ($i = 0; $i < 7; $i++) {
            $currentDate = (clone $monday)->modify("+$i days");
            $dayGrid = $p ? $this->dispoService->getAgendaGrid($m, $currentDate, $p) : [];
            $processedGrid = $this->processGridForWeeklyView($dayGrid, $p);

            $weekData[] = [
                'date' => $currentDate,
                'grid' => $processedGrid,
                'stats' => [
                    'libres' => count(array_filter($dayGrid, fn ($slot) => $slot['type'] === 'libre')),
                    'occupes' => count(array_unique(array_filter(array_map(fn ($slot) => $slot['rdv']?->getId(), $dayGrid)))),
                ],
            ];
        }

        return $this->render('secretaire/agenda.html.twig', [
            'secretaire' => $s,
            'medecin' => $m,
            'planning' => $p,
            'date' => $date,
            'monday' => $monday,
            'weekData' => $weekData,
            'patients' => $this->em->getRepository(Patient::class)->findBy([], ['nomComplet' => 'ASC']),
        ]);
    }

    #[Route('/agenda/disponibilites', name: 'app_secretaire_disponibilites', methods: ['GET'])]
    public function disponibilitesJson(Request $request): JsonResponse
    {
        $s = $this->getUser();
        if (!$s instanceof Secretaire || !$s->getMedecin()) {
            return $this->json(['error' => 'Acces refuse'], 403);
        }

        $m = $s->getMedecin();
        $dateStr = $request->query->get('date', date('Y-m-d'));

        try {
            $date = new \DateTime((string) $dateStr);
        } catch (\Exception) {
            return $this->json(['error' => 'Date invalide'], 400);
        }

        $p = $m->getPlanning();
        $dispos = $this->dispoService->findDispoByDate($m, $date, $p);

        return $this->json([
            'date' => $date->format('Y-m-d'),
            'dispos' => array_map(fn ($d) => $d['heure'], $dispos),
        ]);
    }

    #[Route('/agenda/rdv/creer', name: 'app_secretaire_rdv_creer', methods: ['POST'])]
    public function creerRdv(Request $request): Response
    {
        $s = $this->getUser();
        if (!$s instanceof Secretaire || !$s->getMedecin()) {
            return $this->json(['error' => 'Acces refuse'], 403);
        }

        $m = $s->getMedecin();
        $p = $m->getPlanning();
        if (!$p) {
            return $this->redirectToRoute('app_secretaire_planning_config');
        }

        $pat = $this->em->getRepository(Patient::class)->find($request->request->get('patient_id'));
        if (!$pat || !$this->isCsrfTokenValid('creer_rdv_agenda', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalide');

            return $this->redirectToRoute('app_secretaire_agenda');
        }

        try {
            $start = new \DateTime($request->request->get('date') . ' ' . $request->request->get('heure'));
        } catch (\Exception) {
            $this->addFlash('error', 'Date/heure invalide.');

            return $this->redirectToRoute('app_secretaire_agenda');
        }

        $validation = $this->rdvBookingValidator->validateRequestedSlot($m, $start);
        if ($validation['ok'] !== true) {
            $this->addFlash('error', $validation['message']);

            return $this->redirectToRoute('app_secretaire_agenda', ['date' => $request->request->get('date')]);
        }

        $end = $validation['endAt'] instanceof \DateTimeInterface
            ? \DateTime::createFromInterface($validation['endAt'])
            : (clone $start)->modify('+' . max(1, $p->getDureeConsultation()) . ' minutes');

        $rdv = (new RendezVous())
            ->setMedecin($m)
            ->setPatient($pat)
            ->setDateDebut($start)
            ->setDateFin($end)
            ->setStatut(StatutRendezVous::CONFIRME)
            ->setNote($request->request->get('note'));

        $this->em->persist($rdv);
        $this->createRdvPatientNotification($rdv, true);
        $this->em->flush();
        $this->addFlash('success', 'RDV cree');

        return $this->redirectToRoute('app_secretaire_agenda', ['date' => $request->request->get('date')]);
    }

    private function processGridForWeeklyView(array $grid, ?PlanningMedecin $p): array
    {
        if (empty($grid)) {
            return [];
        }

        $processed = [];
        $currentFreeBlock = null;

        $morningEnd = $p ? $p->getHeureFinMatin() : null;
        $afternoonStart = $p ? $p->getHeureDebutApresMidi() : null;

        foreach ($grid as $slot) {
            $slot['plage'] = $slot['de']->format('H:i') . ' -> ' . $slot['a']->format('H:i');

            if ($slot['type'] === 'libre') {
                if ($currentFreeBlock === null) {
                    $currentFreeBlock = $slot;
                } else {
                    $currentFreeBlock['a'] = $slot['a'];
                    $currentFreeBlock['plage'] = $currentFreeBlock['de']->format('H:i') . ' -> ' . $currentFreeBlock['a']->format('H:i');
                }
            } else {
                if ($currentFreeBlock !== null) {
                    $currentFreeBlock['duree'] = $this->calculateDuration($currentFreeBlock['de'], $currentFreeBlock['a']);
                    $processed[] = $currentFreeBlock;
                    $currentFreeBlock = null;
                }

                $slot['duree'] = $this->calculateDuration($slot['de'], $slot['a']);
                $processed[] = $slot;
            }

            if ($morningEnd && $afternoonStart && $slot['a']->format('H:i') === $morningEnd->format('H:i')) {
                if ($currentFreeBlock !== null) {
                    $currentFreeBlock['duree'] = $this->calculateDuration($currentFreeBlock['de'], $currentFreeBlock['a']);
                    $processed[] = $currentFreeBlock;
                    $currentFreeBlock = null;
                }

                $diffLunch = $morningEnd->diff($afternoonStart);
                if ($diffLunch->h > 0 || $diffLunch->i > 0) {
                    $processed[] = [
                        'type' => 'pause',
                        'plage' => $morningEnd->format('H:i') . ' -> ' . $afternoonStart->format('H:i'),
                        'duree' => $this->calculateDuration($morningEnd, $afternoonStart),
                        'de' => $morningEnd,
                        'a' => $afternoonStart,
                    ];
                }
            }
        }

        if ($currentFreeBlock !== null) {
            $currentFreeBlock['duree'] = $this->calculateDuration($currentFreeBlock['de'], $currentFreeBlock['a']);
            $processed[] = $currentFreeBlock;
        }

        return $processed;
    }

    private function calculateDuration(\DateTimeInterface $start, \DateTimeInterface $end): string
    {
        $diff = $start->diff($end);
        $h = $diff->h;
        $m = $diff->i;

        if ($h > 0) {
            return $m > 0 ? sprintf('%d:%02d', $h, $m) : $h . 'h';
        }

        return $m . 'min';
    }
}
