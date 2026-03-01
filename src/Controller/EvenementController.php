<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Patient;
use App\Entity\Participant;
use App\Enum\StatutEvenement;
use App\Enum\TypeEvenement;
use App\Form\EvenementFormType;
use App\Repository\EvenementRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/evenement')]
class EvenementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EvenementRepository $evenementRepository,
        private ParticipantRepository $participantRepository,
        private SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {
    }

    #[Route('/', name: 'app_evenement_index')]
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $period = (string) $request->query->get('periode', 'tous');
        $type = (string) $request->query->get('type', '');
        $lieu = trim((string) $request->query->get('lieu', ''));
        $dateDebut = (string) $request->query->get('date_debut', '');
        $dateFin = (string) $request->query->get('date_fin', '');

        $qb = $this->evenementRepository->createQueryBuilder('e')
            ->andWhere('e.statut = :statut')
            ->andWhere('e.isActive = :active')
            ->setParameter('statut', StatutEvenement::VALIDE)
            ->setParameter('active', true)
            ->orderBy('e.eventDate', 'ASC');

        if ($search !== '') {
            $qb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.content) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($search) . '%');
        }

        if ($type !== '') {
            $typeEnum = TypeEvenement::tryFrom($type);
            if ($typeEnum !== null) {
                $qb->andWhere('e.typeEvenement = :type')->setParameter('type', $typeEnum);
            }
        }

        if ($lieu !== '') {
            $qb->andWhere('LOWER(e.location) LIKE :lieu')
                ->setParameter('lieu', '%' . mb_strtolower($lieu) . '%');
        }

        $today = new \DateTimeImmutable('today');
        if ($period === 'avenir') {
            $qb->andWhere('e.eventDate >= :today')->setParameter('today', $today);
        } elseif ($period === 'passes') {
            $qb->andWhere('e.eventDate < :today')->setParameter('today', $today);
        }

        if ($dateDebut !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $dateDebut);
            if ($d instanceof \DateTimeImmutable) {
                $qb->andWhere('e.eventDate >= :dateDebut')->setParameter('dateDebut', $d);
            }
        }

        if ($dateFin !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $dateFin);
            if ($d instanceof \DateTimeImmutable) {
                $qb->andWhere('e.eventDate <= :dateFin')->setParameter('dateFin', $d);
            }
        }

        $items = $qb->getQuery()->getResult();

        $mesEvenements = [];
        if ($this->isGranted('ROLE_ORGANISATEUR') || $this->isGranted('ROLE_SECRETAIRE')) {
            $mesEvenements = $this->evenementRepository->findByOrganisateur($this->getUser());
        }

        $aiRecommendations = $this->buildAiRecommendations($items, $this->getUser());

        return $this->render('evenement/index.html.twig', [
            'items' => $items,
            'mesEvenements' => $mesEvenements,
            'search' => $search,
            'periode' => $period,
            'selectedType' => $type,
            'selectedLieu' => $lieu,
            'selectedDateDebut' => $dateDebut,
            'selectedDateFin' => $dateFin,
            'typeOptions' => TypeEvenement::cases(),
            'aiRecommendations' => $aiRecommendations,
        ]);
    }

    #[Route('/statistiques', name: 'app_evenement_stats', methods: ['GET'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function stats(): Response
    {
        $mesEvenements = $this->evenementRepository->findByOrganisateur($this->getUser());
        $totalParticipants = 0;
        $parStatut = [
            'en_attente' => 0,
            'valide' => 0,
            'refuse' => 0,
        ];

        foreach ($mesEvenements as $e) {
            $parStatut[match ($e->getStatut()->value) {
                'EN_ATTENTE' => 'en_attente',
                'VALIDE' => 'valide',
                'REFUSE' => 'refuse',
                default => 'valide',
            }]++;
            $totalParticipants += count($this->participantRepository->findByEvenement($e));
        }

        return $this->render('evenement/stats.html.twig', [
            'mesEvenements' => $mesEvenements,
            'total' => count($mesEvenements),
            'parStatut' => $parStatut,
            'totalParticipants' => $totalParticipants,
        ]);
    }

    #[Route('/nouveau', name: 'app_evenement_new')]
    public function new(Request $request): Response
    {
        $this->denyUnlessEventManager();

        $item = new Evenement();
        $item->setOrganisateur($this->getUser());
        $item->setStatut(StatutEvenement::EN_ATTENTE);

        $form = $this->createForm(EvenementFormType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleAttachmentUpload($item, $form->get('attachmentFile')->getData());
            $this->entityManager->persist($item);
            $this->entityManager->flush();

            $this->addFlash('success', 'Événement créé. Il est en attente de validation par un administrateur.');
            return $this->redirectToRoute('app_evenement_index');
        }

        return $this->render('evenement/form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => false,
            'item' => $item,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function show(Evenement $item): Response
    {
        if ($item->getStatut() !== StatutEvenement::VALIDE && !$this->canManageEvent($item)) {
            throw $this->createAccessDeniedException('Cet événement n\'est pas encore publié.');
        }

        $participants = $this->participantRepository->findByEvenement($item);

        return $this->render('evenement/show.html.twig', [
            'item' => $item,
            'participants' => $participants,
        ]);
    }

    #[Route('/{id}/participer-page', name: 'app_evenement_participer_form', methods: ['GET'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function participerPage(Evenement $item): Response
    {
        if ($item->getStatut() !== StatutEvenement::VALIDE && !$this->canManageEvent($item)) {
            throw $this->createAccessDeniedException('Cet événement n\'est pas encore publié.');
        }

        return $this->render('evenement/participer.html.twig', [
            'item' => $item,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_evenement_edit', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function edit(Request $request, Evenement $item): Response
    {
        if (!$this->canManageEvent($item)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet événement.');
        }

        $form = $this->createForm(EvenementFormType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleAttachmentUpload($item, $form->get('attachmentFile')->getData());
            $this->entityManager->flush();
            $this->addFlash('success', 'Événement modifié avec succès.');
            return $this->redirectToRoute('app_evenement_index');
        }

        return $this->render('evenement/form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => true,
            'item' => $item,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_evenement_delete', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function delete(Request $request, Evenement $item): Response
    {
        if (!$this->canManageEvent($item)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet événement.');
        }

        if ($this->isCsrfTokenValid('delete' . $item->getId(), $request->request->get('_token'))) {
            $this->deleteAttachmentFile($item);
            $this->entityManager->remove($item);
            $this->entityManager->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('app_evenement_index');
    }

    private function canManageEvent(Evenement $item): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (($this->isGranted('ROLE_ORGANISATEUR') || $this->isGranted('ROLE_SECRETAIRE'))
            && $item->getOrganisateur() === $this->getUser()) {
            return true;
        }

        return false;
    }

    #[Route('/{id}/participer', name: 'app_evenement_participer', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function participer(Request $request, Evenement $item): Response
    {
        $max = $item->getMaxParticipants();
        if ($max !== null) {
            $currentCount = $this->participantRepository->count(['evenement' => $item]);
            if ($currentCount >= $max) {
                $this->addFlash('error', 'Le nombre maximum de participants est atteint.');
                return $this->redirectToRoute('app_evenement_show', ['id' => $item->getId()]);
            }
        }

        if (!$this->isCsrfTokenValid('participate' . $item->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $item->getId()]);
        }

        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $email = $request->request->get('email');

        if (!$firstName || !$lastName || !$email) {
            $this->addFlash('error', 'Veuillez remplir le prénom, le nom et l\'email.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $item->getId()]);
        }

        $participant = new Participant();
        $participant->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setEvenement($item);

        $this->entityManager->persist($participant);
        $this->entityManager->flush();

        $this->addFlash('success', 'Inscription confirmée — vous êtes ajouté à la liste des participants.');

        return $this->redirectToRoute('app_evenement_show', ['id' => $item->getId()]);
    }

    #[Route('/{id}/participer/ajax', name: 'app_evenement_participer_ajax', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function participerAjax(Request $request, Evenement $item): JsonResponse
    {
        $max = $item->getMaxParticipants();
        if ($max !== null) {
            $currentCount = $this->participantRepository->count(['evenement' => $item]);
            if ($currentCount >= $max) {
                return $this->json(['error' => 'Le nombre maximum de participants est atteint.'], 409);
            }
        }

        $payload = json_decode((string) $request->getContent(), true);
        $firstName = $payload['firstName'] ?? null;
        $lastName = $payload['lastName'] ?? null;
        $email = $payload['email'] ?? null;

        if (!$firstName || !$lastName || !$email) {
            return $this->json(['error' => 'Missing fields'], 400);
        }

        $participant = new Participant();
        $participant->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setEvenement($item);

        $this->entityManager->persist($participant);
        $this->entityManager->flush();

        return $this->json([
            'id' => $participant->getId(),
            'firstName' => $participant->getFirstName(),
            'lastName' => $participant->getLastName(),
            'email' => $participant->getEmail(),
        ], 201);
    }

    #[Route('/ajax/list', name: 'app_evenement_ajax_list', methods: ['GET'])]
    public function ajaxList(): JsonResponse
    {
        $items = $this->evenementRepository->findAllActive();
        $data = array_map(function (Evenement $m): array {
            return [
                'id' => $m->getId(),
                'title' => $m->getTitle(),
                'content' => $m->getContent(),
                'createdAt' => $m->getCreatedAt()->format('c'),
                'eventDate' => $m->getEventDate()?->format('Y-m-d'),
                'location' => $m->getLocation(),
                'typeEvenement' => $m->getTypeEvenement()?->value,
            ];
        }, $items);

        return $this->json(['items' => $data]);
    }

    #[Route('/ajax/create', name: 'app_evenement_ajax_create', methods: ['POST'])]
    public function ajaxCreate(Request $request): JsonResponse
    {
        $this->denyUnlessEventManager();

        $payload = json_decode((string) $request->getContent(), true);
        $title = $payload['title'] ?? null;

        if (!$title) {
            return $this->json(['error' => 'Title missing'], 400);
        }

        $item = new Evenement();
        $item->setTitle($title);
        $item->setContent($payload['content'] ?? null);
        $item->setLocation($payload['location'] ?? null);
        $item->setIsActive(isset($payload['isActive']) ? (bool) $payload['isActive'] : true);
        $item->setOrganisateur($this->getUser());
        $item->setStatut(StatutEvenement::EN_ATTENTE);

        if (!empty($payload['typeEvenement'])) {
            $enum = TypeEvenement::tryFrom((string) $payload['typeEvenement']);
            if ($enum !== null) {
                $item->setTypeEvenement($enum);
            }
        }

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $this->json([
            'id' => $item->getId(),
            'title' => $item->getTitle(),
        ], 201);
    }

    private function denyUnlessEventManager(): void
    {
        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_ORGANISATEUR')
            && !$this->isGranted('ROLE_SECRETAIRE')
        ) {
            throw $this->createAccessDeniedException('Acces reserve au personnel autorise.');
        }
    }

    private function handleAttachmentUpload(Evenement $item, mixed $fileData): void
    {
        if (!$fileData instanceof UploadedFile) {
            return;
        }

        $targetDir = $this->projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'evenements';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $originalName = pathinfo($fileData->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = (string) $this->slugger->slug($originalName);
        $extension = $fileData->guessExtension() ?: $fileData->getClientOriginalExtension() ?: 'bin';
        $newFilename = $safeName . '-' . bin2hex(random_bytes(6)) . '.' . $extension;

        $fileData->move($targetDir, $newFilename);

        $this->deleteAttachmentFile($item);

        $item->setAttachmentPath('uploads/evenements/' . $newFilename);
        $item->setAttachmentOriginalName($fileData->getClientOriginalName());
    }

    private function deleteAttachmentFile(Evenement $item): void
    {
        $oldPath = $item->getAttachmentPath();
        if (!$oldPath) {
            return;
        }

        $absolute = $this->projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $oldPath);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * @param Evenement[] $items
     * @return array<int, array{event: Evenement, score: int, reason: string}>
     */
    private function buildAiRecommendations(array $items, mixed $user): array
    {
        if (!$user instanceof Patient) {
            return $this->fallbackRecommendations($items, 'Suggestions IA (mode basique)');
        }

        $dossier = $user->getDossierMedical();
        if ($dossier === null) {
            return $this->fallbackRecommendations($items, 'Suggestions IA (mode basique)');
        }

        $signals = $this->extractDossierSignals($dossier->getMaladiesChroniques(), $dossier->getAllergies());
        $scored = [];

        foreach ($items as $item) {
            $result = $this->scoreEventForSignals($item, $signals);
            $score = $result['score'];
            $reason = $result['reason'];
            $scored[] = [
                'event' => $item,
                'score' => $score,
                'reason' => $reason !== '' ? $reason : 'Recommande pour votre profil medical',
            ];
        }

        usort($scored, function (array $a, array $b): int {
            if ($a['score'] === $b['score']) {
                $aDate = $a['event']->getEventDate()?->getTimestamp() ?? PHP_INT_MAX;
                $bDate = $b['event']->getEventDate()?->getTimestamp() ?? PHP_INT_MAX;
                return $aDate <=> $bDate;
            }
            return $b['score'] <=> $a['score'];
        });

        $picked = array_values(array_filter($scored, static fn (array $row): bool => $row['score'] > 0));
        if (count($picked) < 3) {
            $existing = [];
            foreach ($picked as $row) {
                $existing[$row['event']->getId()] = true;
            }
            foreach ($scored as $row) {
                $id = $row['event']->getId();
                if (isset($existing[$id])) {
                    continue;
                }
                $row['score'] = max(1, $row['score']);
                $row['reason'] = 'Recommande pour votre profil medical';
                $picked[] = $row;
                $existing[$id] = true;
                if (count($picked) >= 3) {
                    break;
                }
            }
        }

        return array_slice($picked, 0, 3);
    }

    /**
     * @param Evenement[] $items
     * @return array<int, array{event: Evenement, score: int, reason: string}>
     */
    private function fallbackRecommendations(array $items, string $reason): array
    {
        if (!$items) {
            return [];
        }
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'event' => $item,
                'score' => 1,
                'reason' => $reason,
            ];
        }
        usort($rows, function (array $a, array $b): int {
            $aDate = $a['event']->getEventDate()?->getTimestamp() ?? PHP_INT_MAX;
            $bDate = $b['event']->getEventDate()?->getTimestamp() ?? PHP_INT_MAX;
            return $aDate <=> $bDate;
        });
        return array_slice($rows, 0, 3);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function extractDossierSignals(?string $maladies, ?string $allergies): array
    {
        $text = mb_strtolower(trim(($maladies ?? '') . ' ' . ($allergies ?? '')));
        if ($text === '') {
            return [];
        }

        $signals = [];
        if (str_contains($text, 'diab')) {
            $signals[] = ['key' => 'diab', 'label' => 'Diabete'];
        }
        if (str_contains($text, 'cancer') || str_contains($text, 'sein')) {
            $signals[] = ['key' => 'cancer', 'label' => 'Prevention cancer'];
        }
        if (str_contains($text, 'hypert') || str_contains($text, 'tension') || str_contains($text, 'cardio')) {
            $signals[] = ['key' => 'cardio', 'label' => 'Sante cardiovasculaire'];
        }
        if (str_contains($text, 'asthme') || str_contains($text, 'respir')) {
            $signals[] = ['key' => 'respir', 'label' => 'Respiration'];
        }
        if (str_contains($text, 'allerg')) {
            $signals[] = ['key' => 'allerg', 'label' => 'Allergies'];
        }

        return $signals;
    }

    /**
     * @param array<int, array{key: string, label: string}> $signals
     * @return array{score: int, reason: string}
     */
    private function scoreEventForSignals(Evenement $item, array $signals): array
    {
        $haystack = mb_strtolower(trim(
            ($item->getTitle() ?? '') . ' ' .
            ($item->getContent() ?? '') . ' ' .
            ($item->getTypeEvenement()?->value ?? '') . ' ' .
            ($item->getLocation() ?? '')
        ));

        $score = 0;
        $reasons = [];
        foreach ($signals as $signal) {
            if ($signal['key'] !== '' && str_contains($haystack, $signal['key'])) {
                $score += 2;
                $reasons[] = $signal['label'];
            }
        }

        if ($item->getTypeEvenement() === TypeEvenement::DEPISTAGE_COMMUNAUTAIRE) {
            $score += 1;
        }

        return [
            'score' => $score,
            'reason' => $reasons ? ('Lie a: ' . implode(', ', $reasons)) : '',
        ];
    }
}
