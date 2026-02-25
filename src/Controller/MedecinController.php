<?php
// DOCTOR AGENDA IMPLEMENTATION
namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\DossierMedical;
use App\Entity\Invitation;
use App\Entity\Medecin;
use App\Entity\Ordonnance;
use App\Entity\Patient;
use App\Entity\RapportMedical;
use App\Entity\RendezVous;
use App\Entity\RoleUtilisateur;
use App\Entity\Secretaire;
use App\Entity\StatutInvitation;
use App\Entity\StatutRendezVous;
use App\Entity\Utilisateur;
use App\Form\AjouterSecretaireFormType;
use App\Form\ConsultationFormType;
use App\Form\OrdonnanceFormType;
use App\Form\RapportMedicalFormType;
use App\Repository\ConsultationRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/medecin')]
#[IsGranted('ROLE_MEDECIN')]
class MedecinController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private RendezVousRepository $rdvRepo,
        private ConsultationRepository $consultationRepo,
        private \App\Service\DisponibiliteService $dispoService,
    ) {
    }

    #[Route('/api/medicaments', name: 'app_medecin_api_medicaments', methods: ['GET'])]
    public function apiMedicamentAutocomplete(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');
        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }

        try {
            $jsonPath = $this->getParameter('kernel.project_dir') . '/public/data/medications.json';
            if (!file_exists($jsonPath)) {
                return new JsonResponse([]);
            }

            $meds = json_decode(file_get_contents($jsonPath), true);
            $queryLower = mb_strtolower($query, 'UTF-8');
            
            $results = array_filter($meds, function($item) use ($queryLower) {
                return str_contains(mb_strtolower($item['denomination'], 'UTF-8'), $queryLower);
            });

            $results = array_slice(array_values($results), 0, 10);

            return new JsonResponse($results);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur locale : ' . $e->getMessage()], 500);
        }
    }

    #[Route('', name: 'app_medecin_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin) {
            return $this->redirectToRoute('app_profile');
        }

        $now = new \DateTimeImmutable();
        $dashboardView = (string) $request->query->get('view', 'day');
        if (!in_array($dashboardView, ['day', 'month'], true)) {
            $dashboardView = 'day';
        }

        $requestedMonth = (string) $request->query->get('month', $now->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $requestedMonth)) {
            $requestedMonth = $now->format('Y-m');
        }

        try {
            $selectedMonth = new \DateTimeImmutable($requestedMonth . '-01 00:00:00');
        } catch (\Exception) {
            $selectedMonth = $now->modify('first day of this month')->setTime(0, 0, 0);
        }

        $weekStart = $now->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd = $weekStart->modify('+7 days');

        $patientsSeenThisWeek = $this->consultationRepo->countDistinctPatientsSeenBetween($medecin, $weekStart, $weekEnd);

        $monthStart = $selectedMonth->setTime(0, 0, 0);
        $monthEnd = $monthStart->modify('+1 month');

        $consultationChartLabels = [];
        $consultationChartValues = [];
        $consultationChartTitle = 'Nombre de consultations par jour';
        $consultationChartPeriodLabel = $monthStart->format('m/Y');
        $consultationsThisMonth = 0;
        $selectedPeriodTotal = 0;

        if ($dashboardView === 'month') {
            $yearStart = $monthStart->setDate((int) $monthStart->format('Y'), 1, 1)->setTime(0, 0, 0);
            $yearEnd = $yearStart->modify('+1 year');
            $consultationsByMonthRaw = $this->consultationRepo->countConsultationsByMonthBetween($medecin, $yearStart, $yearEnd);
            $consultationsByMonthMap = [];
            foreach ($consultationsByMonthRaw as $row) {
                $monthKey = substr((string) ($row['monthKey'] ?? ''), 0, 7);
                if ($monthKey === '') {
                    continue;
                }
                $consultationsByMonthMap[$monthKey] = (int) ($row['total'] ?? 0);
            }

            $monthLabels = [
                1 => 'Jan',
                2 => 'Fev',
                3 => 'Mar',
                4 => 'Avr',
                5 => 'Mai',
                6 => 'Juin',
                7 => 'Juil',
                8 => 'Aout',
                9 => 'Sep',
                10 => 'Oct',
                11 => 'Nov',
                12 => 'Dec',
            ];

            $year = (int) $yearStart->format('Y');
            for ($month = 1; $month <= 12; $month++) {
                $monthKey = sprintf('%04d-%02d', $year, $month);
                $consultationChartLabels[] = $monthLabels[$month];
                $consultationChartValues[] = $consultationsByMonthMap[$monthKey] ?? 0;
            }

            $consultationsThisMonth = $consultationsByMonthMap[$monthStart->format('Y-m')] ?? 0;
            $selectedPeriodTotal = array_sum($consultationChartValues);
            $consultationChartTitle = 'Nombre de consultations par mois';
            $consultationChartPeriodLabel = 'Annee ' . $yearStart->format('Y');
        } else {
            $consultationsByDayRaw = $this->consultationRepo->countConsultationsByDayBetween($medecin, $monthStart, $monthEnd);
            $consultationsByDayMap = [];
            foreach ($consultationsByDayRaw as $row) {
                $dayKey = substr((string) ($row['dayKey'] ?? ''), 0, 10);
                if ($dayKey === '') {
                    continue;
                }
                $consultationsByDayMap[$dayKey] = (int) ($row['total'] ?? 0);
            }

            for ($day = $monthStart; $day < $monthEnd; $day = $day->modify('+1 day')) {
                $dayKey = $day->format('Y-m-d');
                $consultationChartLabels[] = $day->format('d/m');
                $consultationChartValues[] = $consultationsByDayMap[$dayKey] ?? 0;
            }

            $consultationsThisMonth = array_sum($consultationChartValues);
            $selectedPeriodTotal = $consultationsThisMonth;
        }

        return $this->render('medecin/index.html.twig', [
            'medecin' => $medecin,
            'patientsSeenThisWeek' => $patientsSeenThisWeek,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'consultationsThisMonth' => $consultationsThisMonth,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'dashboardView' => $dashboardView,
            'selectedMonthInput' => $monthStart->format('Y-m'),
            'consultationChartTitle' => $consultationChartTitle,
            'consultationChartPeriodLabel' => $consultationChartPeriodLabel,
            'selectedPeriodTotal' => $selectedPeriodTotal,
            'consultationChartLabels' => $consultationChartLabels,
            'consultationChartValues' => $consultationChartValues,
        ]);
    }

    #[Route('/secretaires', name: 'app_medecin_secretaires', methods: ['GET', 'POST'])]
    public function secretaires(Request $request): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin) {
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(AjouterSecretaireFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = strtolower(trim((string) $form->get('email')->getData()));
            $existing = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

            if ($existing instanceof Secretaire) {
                if ($existing->getMedecin() === $medecin) {
                    $this->addFlash('warning', 'Ce secrétaire fait déjà partie de votre équipe.');
                    return $this->redirectToRoute('app_medecin_secretaires');
                }
                if ($existing->getMedecin() !== null) {
                    $this->addFlash('error', 'Ce secrétaire est déjà rattaché à un autre médecin.');
                    return $this->redirectToRoute('app_medecin_secretaires');
                }
                $invitationExistante = $this->entityManager->getRepository(Invitation::class)->findOneBy([
                    'medecin' => $medecin,
                    'secretaire' => $existing,
                    'statut' => StatutInvitation::EN_ATTENTE,
                ]);
                if ($invitationExistante) {
                    $this->addFlash('warning', 'Une invitation est déjà en attente pour ce secrétaire.');
                    return $this->redirectToRoute('app_medecin_secretaires');
                }
                $invitation = new Invitation();
                $invitation->setMedecin($medecin);
                $invitation->setSecretaire($existing);
                $invitation->setToken(bin2hex(random_bytes(32)));
                $medecin->addInvitation($invitation);
                $this->entityManager->persist($invitation);
                $this->entityManager->flush();
                $this->sendInvitationSecretaire($invitation, false);
                $this->addFlash('success', 'Invitation envoyée.');
            } elseif ($existing !== null) {
                $this->addFlash('error', 'Un compte existe déjà avec cet email.');
                return $this->redirectToRoute('app_medecin_secretaires');
            } else {
                $nom = explode('@', $email)[0] ?? 'Secretaire';
                $secretaire = new Secretaire();
                $secretaire->setEmail($email);
                $secretaire->setNomComplet(ucfirst($nom));
                $secretaire->setRole(RoleUtilisateur::SECRETAIRE);
                $secretaire->setEmailVerified(true);
                $secretaire->setResetToken(bin2hex(random_bytes(32)));
                $secretaire->setResetTokenExpiresAt(new \DateTimeImmutable('+7 days'));
                $secretaire->setPassword($this->passwordHasher->hashPassword($secretaire, bin2hex(random_bytes(16))));
                $this->entityManager->persist($secretaire);
                $this->entityManager->flush();

                $invitation = new Invitation();
                $invitation->setMedecin($medecin);
                $invitation->setSecretaire($secretaire);
                $invitation->setToken(bin2hex(random_bytes(32)));
                $medecin->addInvitation($invitation);
                $this->entityManager->persist($invitation);
                $this->entityManager->flush();
                $this->sendInvitationSecretaire($invitation, true);
                $this->addFlash('success', 'Invitation envoyée.');
            }

            return $this->redirectToRoute('app_medecin_secretaires');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Email invalide.');
        }

        $secretaires = $medecin->getSecretaires();
        $invitationsEnAttente = $medecin->getInvitations()->filter(
            fn (Invitation $i) => $i->getStatut() === StatutInvitation::EN_ATTENTE
        );

        return $this->render('medecin/secretaires/index.html.twig', [
            'secretaires' => $secretaires,
            'invitationsEnAttente' => $invitationsEnAttente,
            'form' => $form,
        ]);
    }

    #[Route('/rendez-vous', name: 'app_medecin_rendez_vous', methods: ['GET'])]
    public function rendezVous(): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin) {
            return $this->redirectToRoute('app_profile');
        }

        $rdvs = $this->rdvRepo->findByMedecin($medecin);

        return $this->render('medecin/rendez_vous.html.twig', [
            'medecin' => $medecin,
            'rdvs' => $rdvs,
        ]);
    }

    #[Route('/agenda', name: 'app_medecin_agenda', methods: ['GET'])]
    public function agenda(Request $request): Response
    {
        /** @var Medecin $m */
        $m = $this->getUser();
        if (!$m instanceof Medecin) return $this->redirectToRoute('app_profile');
        
        $dateStr = $request->query->get('date', date('Y-m-d'));
        try { 
            $date = new \DateTime($dateStr); 
        } catch (\Exception $e) { 
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
                    'libres' => count(array_filter($dayGrid, fn($s) => $s['type'] === 'libre')),
                    'occupes' => count(array_unique(array_filter(array_map(fn($s) => $s['rdv']?->getId(), $dayGrid)))),
                ]
            ];
        }

        return $this->render('medecin/agenda.html.twig', [
            'medecin' => $m, 'planning' => $p, 
            'date' => $date,
            'monday' => $monday,
            'weekData' => $weekData,
            'patients' => $this->entityManager->getRepository(Patient::class)->findBy([], ['nomComplet' => 'ASC']),
        ]);
    }

    #[Route('/planning/configurer', name: 'app_medecin_planning_config', methods: ['GET', 'POST'])]
    public function planningConfigurer(Request $request): Response
    {
        /** @var Medecin $m */
        $m = $this->getUser();
        if (!$m instanceof Medecin) return $this->redirectToRoute('app_profile');

        $p = $m->getPlanning() ?: (new \App\Entity\PlanningMedecin())->setMedecin($m);
        $form = $this->createForm(\App\Form\PlanningMedecinType::class, $p);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($p);
            $m->setPlanning($p);
            $this->entityManager->flush();
            $this->addFlash('success', 'Planning mis à jour.');
            return $this->redirectToRoute('app_medecin_agenda');
        }
        
        return $this->render('medecin/planning_config.html.twig', [
            'form' => $form->createView(), 'medecin' => $m, 'planning' => $p,
        ]);
    }

    #[Route('/agenda/rdv/creer', name: 'app_medecin_rdv_creer', methods: ['POST'])]
    public function creerRdv(Request $request): Response
    {
        /** @var Medecin $m */
        $m = $this->getUser();
        if (!$m instanceof Medecin) return $this->json(['error'=>'Accès refusé'], 403);
        
        $p = $m->getPlanning();
        if (!$p) return $this->redirectToRoute('app_medecin_planning_config');
        
        $pat = $this->entityManager->getRepository(Patient::class)->find($request->request->get('patient_id'));
        if (!$pat || !$this->isCsrfTokenValid('creer_rdv_agenda', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalide'); return $this->redirectToRoute('app_medecin_agenda');
        }
        
        $start = new \DateTime($request->request->get('date').' '.$request->request->get('heure'));
        $end = (clone $start)->modify("+".($p ? $p->getDureeConsultation() : 30)." minutes");
        
        $rdv = (new RendezVous())->setMedecin($m)->setPatient($pat)->setDateDebut($start)->setDateFin($end)->setStatut(StatutRendezVous::CONFIRME)->setNote($request->request->get('note'));
        $this->entityManager->persist($rdv); $this->entityManager->flush();
        $this->addFlash('success', 'RDV créé');
        return $this->redirectToRoute('app_medecin_agenda', ['date'=>$request->request->get('date')]);
    }

    #[Route('/consultations', name: 'app_medecin_consultations', methods: ['GET'])]
    public function consultations(): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin) {
            return $this->redirectToRoute('app_profile');
        }

        $consultations = $medecin->getConsultations();

        return $this->render('medecin/consultations.html.twig', [
            'medecin' => $medecin,
            'consultations' => $consultations,
        ]);
    }

    #[Route('/consultation/nouvelle/{id}', name: 'app_medecin_consultation_nouvelle', methods: ['GET', 'POST'])]
    public function nouvelleConsultation(Request $request, RendezVous $rdv): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $rdv->getMedecin() !== $medecin) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }

        if ($rdv->getConsultation()) {
            $this->addFlash('warning', 'Une consultation existe déjà pour ce rendez-vous.');
            return $this->redirectToRoute('app_medecin_consultations');
        }

        $patient = $rdv->getPatient();
        $dossier = $patient->getDossierMedical();
        if (!$dossier) {
            $dossier = new DossierMedical();
            $dossier->setPatient($patient);
            $patient->setDossierMedical($dossier);
            $this->entityManager->persist($dossier);
            $this->entityManager->flush();
        }

        $consultation = new Consultation();
        $consultation->setDate($rdv->getDateDebut());
        $consultation->setRendezVous($rdv);
        $consultation->setDossierMedical($dossier);
        $consultation->setMedecin($medecin);
        $rdv->setStatut(StatutRendezVous::TERMINE);

        $form = $this->createForm(ConsultationFormType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $medecin->addConsultation($consultation);
            $dossier->addConsultation($consultation);
            $rdv->setConsultation($consultation);
            $this->entityManager->persist($consultation);
            $this->entityManager->flush();
            $this->addFlash('success', 'Consultation créée.');
            return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
        }

        return $this->render('medecin/consultation_nouvelle.html.twig', [
            'medecin' => $medecin,
            'rdv' => $rdv,
            'consultation' => $consultation,
            'form' => $form,
        ]);
    }

    #[Route('/dossier-patient/{id}', name: 'app_medecin_dossier_patient', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function dossierPatient(Patient $patient): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin) {
            return $this->redirectToRoute('app_profile');
        }

        $hasRdv = $this->rdvRepo->findOneBy(['patient' => $patient, 'medecin' => $medecin]) !== null;
        if (!$hasRdv) {
            $this->addFlash('error', 'Vous n\'avez aucun rendez-vous avec ce patient.');
            return $this->redirectToRoute('app_medecin_rendez_vous');
        }

        $dossier = $patient->getDossierMedical();
        $consultations = [];
        if ($dossier) {
            $consultations = $dossier->getConsultations()->toArray();
            usort($consultations, fn (Consultation $a, Consultation $b) => $b->getDate() <=> $a->getDate());
        }

        return $this->render('medecin/dossier_patient.html.twig', [
            'medecin' => $medecin,
            'patient' => $patient,
            'dossier' => $dossier,
            'consultations' => $consultations,
        ]);
    }

    #[Route('/consultation/{id}', name: 'app_medecin_consultation_voir', methods: ['GET'])]
    public function voirConsultation(Consultation $consultation): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin) {
            return $this->redirectToRoute('app_profile');
        }
        $isOwnConsultation = $consultation->getMedecin()->getId() === $medecin->getId();
        $patient = $consultation->getDossierMedical()?->getPatient();
        $hasRdvWithPatient = $patient && $this->rdvRepo->findOneBy(['patient' => $patient, 'medecin' => $medecin]) !== null;
        if (!$isOwnConsultation && !$hasRdvWithPatient) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }

        return $this->render('medecin/consultation_voir.html.twig', [
            'medecin' => $medecin,
            'consultation' => $consultation,
            'is_own_consultation' => $isOwnConsultation,
        ]);
    }

    #[Route('/consultation/{id}/modifier', name: 'app_medecin_consultation_modifier', methods: ['GET', 'POST'])]
    public function modifierConsultation(Request $request, Consultation $consultation): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $consultation->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }
        $form = $this->createForm(ConsultationFormType::class, $consultation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Consultation modifiée.');
            return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
        }
        return $this->render('medecin/consultation_modifier.html.twig', [
            'medecin' => $medecin,
            'consultation' => $consultation,
            'form' => $form,
        ]);
    }

    #[Route('/consultation/{id}/supprimer', name: 'app_medecin_consultation_supprimer', methods: ['POST'])]
    public function supprimerConsultation(Request $request, Consultation $consultation): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $consultation->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }
        $token = $request->request->get('_token');
        if (!$token || !$this->csrfTokenManager->isTokenValid(new CsrfToken('supprimer_consultation_' . $consultation->getId(), $token))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_medecin_consultations');
        }
        $rdv = $consultation->getRendezVous();
        if ($rdv) {
            $rdv->setConsultation(null);
        }
        $this->entityManager->remove($consultation);
        $this->entityManager->flush();
        $this->addFlash('success', 'Consultation supprimée.');
        return $this->redirectToRoute('app_medecin_consultations');
    }

    #[Route('/consultation/{id}/ordonnance', name: 'app_medecin_ordonnance_ajouter', methods: ['GET', 'POST'])]
    public function ajouterOrdonnance(Request $request, Consultation $consultation): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $consultation->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }

        $ordonnance = new Ordonnance();
        $form = $this->createForm(OrdonnanceFormType::class, $ordonnance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $consultation->addOrdonnance($ordonnance);
            $this->entityManager->persist($ordonnance);
            $this->entityManager->flush();
            $this->addFlash('success', 'Ordonnance ajoutée.');
            return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
        }

        return $this->render('medecin/ordonnance_ajouter.html.twig', [
            'medecin' => $medecin,
            'consultation' => $consultation,
            'form' => $form,
        ]);
    }

    #[Route('/ordonnance/{id}/modifier', name: 'app_medecin_ordonnance_modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierOrdonnance(Request $request, Ordonnance $ordonnance): Response
    {
        $consultation = $ordonnance->getConsultation();
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || !$consultation || $consultation->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }
        $form = $this->createForm(OrdonnanceFormType::class, $ordonnance);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Ordonnance modifiée.');
            return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
        }
        return $this->render('medecin/ordonnance_modifier.html.twig', [
            'medecin' => $medecin,
            'ordonnance' => $ordonnance,
            'consultation' => $consultation,
            'form' => $form,
        ]);
    }

    #[Route('/ordonnance/{id}/supprimer', name: 'app_medecin_ordonnance_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimerOrdonnance(Request $request, Ordonnance $ordonnance): Response
    {
        $consultation = $ordonnance->getConsultation();
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || !$consultation || $consultation->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }
        $token = $request->request->get('_token');
        if (!$token || !$this->csrfTokenManager->isTokenValid(new CsrfToken('supprimer_ordonnance_' . $ordonnance->getId(), $token))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
        }
        $this->entityManager->remove($ordonnance);
        $this->entityManager->flush();
        $this->addFlash('success', 'Ordonnance supprimée.');
        return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
    }

    #[Route('/ordonnance/{id}/pdf', name: 'app_medecin_ordonnance_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadOrdonnancePdf(Ordonnance $ordonnance): Response
    {
        $consultation = $ordonnance->getConsultation();
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin || !$consultation || $consultation->getMedecin()->getId() !== $medecin->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('medecin/prescription_pdf.html.twig', [
            'ordonnance' => $ordonnance,
            'consultation' => $consultation,
            'medecin' => $medecin,
            'patient' => $consultation->getDossierMedical()?->getPatient(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        $filename = 'ordonnance_' . $ordonnance->getId() . '.pdf';

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/consultation/{id}/rapport', name: 'app_medecin_rapport_ajouter', methods: ['GET', 'POST'])]
    public function ajouterRapport(Request $request, Consultation $consultation): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $consultation->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }

        $rapport = new RapportMedical();
        $form = $this->createForm(RapportMedicalFormType::class, $rapport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $consultation->addRapportMedical($rapport);
            $this->entityManager->persist($rapport);
            $this->entityManager->flush();
            $this->addFlash('success', 'Rapport médical ajouté.');
            return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
        }

        return $this->render('medecin/rapport_ajouter.html.twig', [
            'medecin' => $medecin,
            'consultation' => $consultation,
            'form' => $form,
        ]);
    }

    #[Route('/rapport/{id}/modifier', name: 'app_medecin_rapport_modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierRapport(Request $request, RapportMedical $rapport): Response
    {
        $consultation = $rapport->getConsultation();
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || !$consultation || $consultation->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }
        $form = $this->createForm(RapportMedicalFormType::class, $rapport);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Rapport médical modifié.');
            return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
        }
        return $this->render('medecin/rapport_modifier.html.twig', [
            'medecin' => $medecin,
            'rapport' => $rapport,
            'consultation' => $consultation,
            'form' => $form,
        ]);
    }

    #[Route('/rapport/{id}/supprimer', name: 'app_medecin_rapport_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimerRapport(Request $request, RapportMedical $rapport): Response
    {
        $consultation = $rapport->getConsultation();
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || !$consultation || $consultation->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_consultations');
        }
        $token = $request->request->get('_token');
        if (!$token || !$this->csrfTokenManager->isTokenValid(new CsrfToken('supprimer_rapport_' . $rapport->getId(), $token))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
        }
        $this->entityManager->remove($rapport);
        $this->entityManager->flush();
        $this->addFlash('success', 'Rapport médical supprimé.');
        return $this->redirectToRoute('app_medecin_consultation_voir', ['id' => $consultation->getId()]);
    }

    #[Route('/rendez-vous/{id}/annuler', name: 'app_medecin_rdv_annuler', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function annulerRdv(Request $request, RendezVous $rdv): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $rdv->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_rendez_vous');
        }
        if ($rdv->getStatut() === StatutRendezVous::ANNULE) {
            $this->addFlash('warning', 'Ce rendez-vous est déjà annulé.');
            return $this->redirectToRoute('app_medecin_rendez_vous');
        }
        $token = $request->request->get('_token');
        if (!$token || !$this->csrfTokenManager->isTokenValid(new CsrfToken('annuler_rdv_' . $rdv->getId(), $token))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_medecin_rendez_vous');
        }
        $rdv->setStatut(StatutRendezVous::ANNULE);
        $this->entityManager->flush();
        $this->addFlash('success', 'Rendez-vous annulé.');
        return $this->redirectToRoute('app_medecin_rendez_vous');
    }

    #[Route('/secretaires/{id}/retirer', name: 'app_medecin_secretaire_retirer', methods: ['POST'])]
    public function retirerSecretaire(Request $request, Secretaire $secretaire): Response
    {
        $token = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('retirer_secretaire_' . $secretaire->getId(), $token))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_medecin_secretaires');
        }

        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $secretaire->getMedecin()->getId() !== $medecin->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_medecin_secretaires');
        }

        $secretaire->setMedecin(null);
        $this->entityManager->flush();
        $this->addFlash('success', 'Secrétaire retiré de votre équipe.');

        return $this->redirectToRoute('app_medecin_secretaires');
    }

    private function sendInvitationSecretaire(Invitation $invitation, bool $definirMotDePasse): void
    {
        $secretaire = $invitation->getSecretaire();
        $medecin = $invitation->getMedecin();
        $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: null;

        $acceptUrl = $appUrl
            ? rtrim($appUrl, '/') . $this->urlGenerator->generate('app_invitation_accepter', ['token' => $invitation->getToken()])
            : $this->urlGenerator->generate('app_invitation_accepter', ['token' => $invitation->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);
        $refuseUrl = $appUrl
            ? rtrim($appUrl, '/') . $this->urlGenerator->generate('app_invitation_refuser', ['token' => $invitation->getToken()])
            : $this->urlGenerator->generate('app_invitation_refuser', ['token' => $invitation->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);

        $fromAddress = $_ENV['MAILER_FROM'] ?? getenv('MAILER_FROM') ?: 'MediConnect <noreply@mediconnect.com>';
        $emailMessage = (new TemplatedEmail())
            ->from(Address::create($fromAddress))
            ->to($secretaire->getEmail())
            ->subject('Invitation - MediConnect')
            ->htmlTemplate('emails/invitation_secretaire.html.twig')
            ->context([
                'invitation' => $invitation,
                'secretaire' => $secretaire,
                'medecin' => $medecin,
                'definirMotDePasse' => $definirMotDePasse,
                'acceptUrl' => $acceptUrl,
                'refuseUrl' => $refuseUrl,
            ]);

        try {
            $this->mailer->send($emailMessage);
        } catch (TransportExceptionInterface $e) {
            error_log('[MediConnect] Erreur email: ' . $e->getMessage());
        }
    }

    private function processGridForWeeklyView(array $grid, ?\App\Entity\PlanningMedecin $p): array
    {
        if (empty($grid)) return [];
        $processed = [];
        $currentFreeBlock = null;
        
        $morningEnd = $p ? $p->getHeureFinMatin() : null;
        $afternoonStart = $p ? $p->getHeureDebutApresMidi() : null;
        
        foreach ($grid as $slot) {
            $slot['plage'] = $slot['de']->format('H:i') . '  ' . $slot['a']->format('H:i');

            if ($slot['type'] === 'libre') {
                if ($currentFreeBlock === null) {
                    $currentFreeBlock = $slot;
                } else {
                    $currentFreeBlock['a'] = $slot['a'];
                    $currentFreeBlock['plage'] = $currentFreeBlock['de']->format('H:i') . '  ' . $currentFreeBlock['a']->format('H:i');
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
                        'plage' => $morningEnd->format('H:i') . '  ' . $afternoonStart->format('H:i'),
                        'duree' => $this->calculateDuration($morningEnd, $afternoonStart),
                        'de' => $morningEnd,
                        'a' => $afternoonStart
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
        if ($h > 0) return $m > 0 ? sprintf('%d:%02d', $h, $m) : $h . 'h';
        return $m . 'min';
    }
}


