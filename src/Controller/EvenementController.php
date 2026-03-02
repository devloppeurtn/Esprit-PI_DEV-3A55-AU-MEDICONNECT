<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Participant;
<<<<<<< HEAD
use App\Entity\AvisEvenement;
use App\Enum\StatutEvenement;
use App\Form\EvenementFormType;
use App\Form\AvisEvenementFormType;
use App\Repository\EvenementRepository;
use App\Repository\ParticipantRepository;
use App\Repository\AvisEvenementRepository;
use App\Service\WeatherService;
use App\Service\EventRecommendationService;
=======
<<<<<<< HEAD
=======
use App\Enum\StatutEvenement;
>>>>>>> isramedi
use App\Form\EvenementFormType;
use App\Repository\EvenementRepository;
use App\Repository\ParticipantRepository;
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
<<<<<<< HEAD
use Symfony\Component\Security\Http\Attribute\IsGranted;
=======
<<<<<<< HEAD
=======
use Symfony\Component\Security\Http\Attribute\IsGranted;
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1

#[Route('/evenement')]
class EvenementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EvenementRepository $evenementRepository,
<<<<<<< HEAD
        private ParticipantRepository $participantRepository,
        private AvisEvenementRepository $avisEvenementRepository,
        private WeatherService $weatherService,
        private EventRecommendationService $recommendationService,
        private \App\Repository\EventFeedbackRepository $feedbackRepository
=======
        private ParticipantRepository $participantRepository
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    ) {
    }

    #[Route('/', name: 'app_evenement_index')]
<<<<<<< HEAD
=======
<<<<<<< HEAD
    public function index(): Response
    {
        $items = $this->evenementRepository->findAll();

        return $this->render('evenement/index.html.twig', [
            'items' => $items,
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $period = $request->query->get('periode', 'tous'); // tous, avenir, passes

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

        $today = new \DateTimeImmutable('today');
        if ($period === 'avenir') {
            $qb->andWhere('e.eventDate >= :today')
               ->setParameter('today', $today);
        } elseif ($period === 'passes') {
            $qb->andWhere('e.eventDate < :today')
               ->setParameter('today', $today);
        }

        $items = $qb->getQuery()->getResult();
<<<<<<< HEAD
        
        // Ajouter les données météo pour chaque événement
        $weatherData = [];
        foreach ($items as $item) {
            if ($item->getEventDate() && $item->getLocation()) {
                $weather = $this->weatherService->getWeatherForDate(
                    $item->getEventDate(),
                    $item->getLocation()
                );
                if ($weather) {
                    $weatherData[$item->getId()] = $weather;
                }
            }
        }
        
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        $mesEvenements = [];
        if ($this->isGranted('ROLE_ORGANISATEUR')) {
            $mesEvenements = $this->evenementRepository->findByOrganisateur($this->getUser());
        }

<<<<<<< HEAD
        // Recommandations IA pour les patients
        $aiRecommendations = [];
        if ($this->isGranted('ROLE_PATIENT')) {
            $user = $this->getUser();
            if ($user instanceof \App\Entity\Patient) {
                $suggestions = $this->recommendationService->getSuggestedEvents($user, 3);
                $aiRecommendations = $suggestions;
            }
        }

=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        return $this->render('evenement/index.html.twig', [
            'items' => $items,
            'mesEvenements' => $mesEvenements,
            'search' => $search,
            'periode' => $period,
<<<<<<< HEAD
            'weatherData' => $weatherData,
            'aiRecommendations' => $aiRecommendations,
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        ]);
    }

    #[Route('/nouveau', name: 'app_evenement_new')]
<<<<<<< HEAD
=======
<<<<<<< HEAD
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
        $item = new Evenement();
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function new(Request $request): Response
    {
        $item = new Evenement();
        $item->setOrganisateur($this->getUser());
        $item->setStatut(StatutEvenement::EN_ATTENTE);
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        $form = $this->createForm(EvenementFormType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($item);
            $this->entityManager->flush();

<<<<<<< HEAD
            $this->addFlash('success', 'Événement créé. Il est en attente de validation par un administrateur.');
=======
<<<<<<< HEAD
            $this->addFlash('success', 'Événement créé avec succès.');
=======
            $this->addFlash('success', 'Événement créé. Il est en attente de validation par un administrateur.');
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            return $this->redirectToRoute('app_evenement_index');
        }

        return $this->render('evenement/form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => false,
            'item' => $item,
        ]);
    }

<<<<<<< HEAD
=======
<<<<<<< HEAD
    #[Route('/{id}', name: 'app_evenement_show')]
    public function show(Evenement $item): Response
    {
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[Route('/{id}', name: 'app_evenement_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function show(Evenement $item): Response
    {
        if ($item->getStatut() !== StatutEvenement::VALIDE && !$this->canManageEvent($item)) {
            throw $this->createAccessDeniedException('Cet événement n\'est pas encore publié.');
        }
<<<<<<< HEAD
        $participants = $this->participantRepository->findByEvenement($item);
        
        // Fetch weather data if location and date are available
        $weather = null;
        if ($item->getLocation() && $item->getEventDate()) {
            $weather = $this->weatherService->getWeatherForDate($item->getEventDate(), $item->getLocation());
        }
        
        // Fetch feedback statistics
        $averageRating = $this->feedbackRepository->getAverageRating($item);
        $totalFeedbacks = $this->feedbackRepository->countByEvent($item);
        
        return $this->render('evenement/show.html.twig', [
            'item' => $item,
            'participants' => $participants,
            'weather' => $weather,
            'averageRating' => $averageRating,
            'totalFeedbacks' => $totalFeedbacks,
        ]);
    }

=======
>>>>>>> isramedi
        $participants = $this->participantRepository->findByEvenement($item);
        return $this->render('evenement/show.html.twig', ['item' => $item, 'participants' => $participants]);
    }

<<<<<<< HEAD
    #[Route('/{id}/modifier', name: 'app_evenement_edit')]
    public function edit(Request $request, Evenement $item): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        $form = $this->createForm(EvenementFormType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

<<<<<<< HEAD
=======
<<<<<<< HEAD
    #[Route('/{id}/supprimer', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $item): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[Route('/{id}/supprimer', name: 'app_evenement_delete', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function delete(Request $request, Evenement $item): Response
    {
        if (!$this->canManageEvent($item)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet événement.');
        }
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($item);
            $this->entityManager->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('app_evenement_index');
    }

<<<<<<< HEAD
=======
<<<<<<< HEAD
    // Participation endpoints
    #[Route('/{id}/participer', name: 'app_evenement_participer', methods: ['POST'])]
    public function participer(Request $request, Evenement $item): Response
    {
        if (! $this->isCsrfTokenValid('participate'.$item->getId(), $request->request->get('_token'))) {
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    private function canManageEvent(Evenement $item): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($this->isGranted('ROLE_ORGANISATEUR') && $item->getOrganisateur() === $this->getUser()) {
            return true;
        }
        return false;
    }

    #[Route('/{id}/participer', name: 'app_evenement_participer', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function participer(Request $request, Evenement $item): Response
    {
        if (!$this->isCsrfTokenValid('participate'.$item->getId(), $request->request->get('_token'))) {
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $item->getId()]);
        }

        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $email = $request->request->get('email');

<<<<<<< HEAD
        if (!$firstName || !$lastName || !$email) {
=======
<<<<<<< HEAD
        if (! $firstName || ! $lastName || ! $email) {
=======
        if (!$firstName || !$lastName || !$email) {
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            $this->addFlash('error', 'Veuillez remplir le prénom, le nom et l\'email.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $item->getId()]);
        }

<<<<<<< HEAD
        // Check if max participants limit is reached
        if ($item->getMaxParticipants() !== null) {
            $currentParticipantCount = count($this->participantRepository->findByEvenement($item));
            if ($currentParticipantCount >= $item->getMaxParticipants()) {
                $this->addFlash('error', 'Désolé, le nombre maximal de participants (' . $item->getMaxParticipants() . ') a été atteint.');
                return $this->redirectToRoute('app_evenement_show', ['id' => $item->getId()]);
            }
        }

=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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

<<<<<<< HEAD
    #[Route('/{id}/participer/ajax', name: 'app_evenement_participer_ajax', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
=======
<<<<<<< HEAD
    #[Route('/{id}/participer/ajax', name: 'app_evenement_participer_ajax', methods: ['POST'])]
=======
    #[Route('/{id}/participer/ajax', name: 'app_evenement_participer_ajax', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    public function participerAjax(Request $request, Evenement $item): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $firstName = $payload['firstName'] ?? null;
        $lastName = $payload['lastName'] ?? null;
        $email = $payload['email'] ?? null;

<<<<<<< HEAD
        if (!$firstName || !$lastName || !$email) {
            return $this->json(['error' => 'Missing fields'], 400);
        }

        // Check if max participants limit is reached
        if ($item->getMaxParticipants() !== null) {
            $currentParticipantCount = count($this->participantRepository->findByEvenement($item));
            if ($currentParticipantCount >= $item->getMaxParticipants()) {
                return $this->json(['error' => 'Le nombre maximal de participants (' . $item->getMaxParticipants() . ') a été atteint.'], 400);
            }
        }

=======
<<<<<<< HEAD
        if (! $firstName || ! $lastName || ! $email) {
=======
        if (!$firstName || !$lastName || !$email) {
>>>>>>> isramedi
            return $this->json(['error' => 'Missing fields'], 400);
        }

>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        $participant = new Participant();
        $participant->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setEvenement($item);

        $this->entityManager->persist($participant);
        $this->entityManager->flush();

<<<<<<< HEAD
=======
<<<<<<< HEAD
        return $this->json(['id' => $participant->getId(), 'firstName' => $participant->getFirstName(), 'lastName' => $participant->getLastName(), 'email' => $participant->getEmail()], 201);
    }

    // AJAX endpoints
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        return $this->json([
            'id' => $participant->getId(),
            'firstName' => $participant->getFirstName(),
            'lastName' => $participant->getLastName(),
            'email' => $participant->getEmail()
        ], 201);
    }

<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[Route('/ajax/list', name: 'app_evenement_ajax_list', methods: ['GET'])]
    public function ajaxList(): JsonResponse
    {
        $items = $this->evenementRepository->findAllActive();
        $data = array_map(function (Evenement $m) {
            return [
                'id' => $m->getId(),
                'title' => $m->getTitle(),
                'content' => $m->getContent(),
                'createdAt' => $m->getCreatedAt()->format('c'),
                'eventDate' => $m->getEventDate() ? $m->getEventDate()->format('Y-m-d') : null,
            ];
        }, $items);

        return $this->json(['items' => $data]);
    }

    #[Route('/ajax/create', name: 'app_evenement_ajax_create', methods: ['POST'])]
<<<<<<< HEAD
    #[IsGranted('ROLE_ORGANISATEUR')]
=======
<<<<<<< HEAD
=======
    #[IsGranted('ROLE_ORGANISATEUR')]
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    public function ajaxCreate(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $title = $payload['title'] ?? null;

        if (!$title) {
            return $this->json(['error' => 'Title missing'], 400);
        }

        $item = new Evenement();
        $item->setTitle($title);
        $item->setContent($payload['content'] ?? null);
        $item->setIsActive(isset($payload['isActive']) ? (bool)$payload['isActive'] : true);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $this->json(['id' => $item->getId(), 'title' => $item->getTitle()], 201);
    }
<<<<<<< HEAD

    #[Route('/{id}/avis', name: 'app_evenement_avis', methods: ['GET', 'POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function laisserAvis(Request $request, Evenement $evenement): Response
    {
        $participant = $this->participantRepository->findOneBy(['email' => $request->query->get('email')]);
        
        if (!$participant) {
            $this->addFlash('error', 'Veuillez d\'abord vous inscrire à cet événement.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        // Check if participant already has feedback for this event
        $existingAvis = $this->avisEvenementRepository->findOneBy([
            'evenement' => $evenement,
            'participant' => $participant,
        ]);

        if ($existingAvis) {
            $avis = $existingAvis;
            $isEdit = true;
        } else {
            $avis = new AvisEvenement();
            $avis->setEvenement($evenement);
            $avis->setParticipant($participant);
            $isEdit = false;
        }

        $form = $this->createForm(AvisEvenementFormType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($avis);
            $this->entityManager->flush();

            $message = $isEdit ? 'Merci d\'avoir mis à jour votre avis!' : 'Merci pour votre retour!';
            $this->addFlash('success', $message);
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        return $this->render('evenement/avis.html.twig', [
            'form' => $form,
            'evenement' => $evenement,
            'isEdit' => $isEdit,
        ]);
    }

    #[Route('/{id}/avis/consulter', name: 'app_evenement_consulter_avis', methods: ['GET'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function consulterAvis(Evenement $evenement): Response
    {
        $avis = $this->avisEvenementRepository->findBy(
            ['evenement' => $evenement],
            ['dateCreation' => 'DESC']
        );

        // Calculate average rating
        $moyenneNote = 0;
        if (count($avis) > 0) {
            $totalNote = array_sum(array_map(fn($a) => $a->getNote(), $avis));
            $moyenneNote = round($totalNote / count($avis), 1);
        }

        return $this->render('evenement/consulter_avis.html.twig', [
            'evenement' => $evenement,
            'avis' => $avis,
            'moyenneNote' => $moyenneNote,
        ]);
    }

    #[Route('/{id}/feedback', name: 'app_evenement_feedback', methods: ['GET', 'POST'])]
    public function leaveFeedback(Request $request, Evenement $evenement): Response
    {
        // Vérifier que l'événement est terminé
        if ($evenement->getEventDate() && $evenement->getEventDate() > new \DateTimeImmutable()) {
            $this->addFlash('error', 'Vous ne pouvez laisser un feedback que pour un événement terminé.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        // Récupérer l'email du participant depuis la requête
        $participantEmail = $request->query->get('email') ?? $request->request->get('email');
        
        if (!$participantEmail) {
            $this->addFlash('error', 'Email du participant requis.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        // Vérifier que le participant existe et a participé à cet événement
        $participant = $this->participantRepository->createQueryBuilder('p')
            ->where('p.email = :email')
            ->andWhere('p.evenement = :evenement')
            ->setParameter('email', $participantEmail)
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$participant) {
            $this->addFlash('error', 'Vous devez avoir participé à cet événement pour laisser un feedback.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        // Vérifier si un feedback existe déjà
        $existingFeedback = $this->feedbackRepository->findByParticipantAndEvent($participant, $evenement);
        
        if ($existingFeedback) {
            $feedback = $existingFeedback;
            $isEdit = true;
        } else {
            $feedback = new \App\Entity\EventFeedback();
            $feedback->setParticipant($participant);
            $feedback->setEvenement($evenement);
            $isEdit = false;
        }

        $form = $this->createForm(\App\Form\EventFeedbackFormType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isEdit) {
                $feedback->setUpdatedAt(new \DateTimeImmutable());
            }
            
            $this->entityManager->persist($feedback);
            $this->entityManager->flush();

            $message = $isEdit ? 'Votre feedback a été mis à jour avec succès!' : 'Merci pour votre feedback!';
            $this->addFlash('success', $message);
            
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        return $this->render('evenement/feedback.html.twig', [
            'form' => $form,
            'evenement' => $evenement,
            'participant' => $participant,
            'isEdit' => $isEdit,
        ]);
    }

    #[Route('/{id}/feedbacks', name: 'app_evenement_feedbacks', methods: ['GET'])]
    public function viewFeedbacks(Evenement $evenement): Response
    {
        $feedbacks = $this->feedbackRepository->findByEvent($evenement);
        $averageRating = $this->feedbackRepository->getAverageRating($evenement);
        $totalFeedbacks = $this->feedbackRepository->countByEvent($evenement);
        $ratingDistribution = $this->feedbackRepository->getRatingDistribution($evenement);

        return $this->render('evenement/feedbacks.html.twig', [
            'evenement' => $evenement,
            'feedbacks' => $feedbacks,
            'averageRating' => $averageRating,
            'totalFeedbacks' => $totalFeedbacks,
            'ratingDistribution' => $ratingDistribution,
        ]);
    }
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
}
