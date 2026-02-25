<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Participant;
<<<<<<< HEAD
=======
use App\Enum\StatutEvenement;
>>>>>>> isramedi
use App\Form\EvenementFormType;
use App\Repository\EvenementRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
<<<<<<< HEAD
=======
use Symfony\Component\Security\Http\Attribute\IsGranted;
>>>>>>> isramedi

#[Route('/evenement')]
class EvenementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EvenementRepository $evenementRepository,
        private ParticipantRepository $participantRepository
    ) {
    }

    #[Route('/', name: 'app_evenement_index')]
<<<<<<< HEAD
    public function index(): Response
    {
        $items = $this->evenementRepository->findAll();

        return $this->render('evenement/index.html.twig', [
            'items' => $items,
=======
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
        $mesEvenements = [];
        if ($this->isGranted('ROLE_ORGANISATEUR')) {
            $mesEvenements = $this->evenementRepository->findByOrganisateur($this->getUser());
        }

        return $this->render('evenement/index.html.twig', [
            'items' => $items,
            'mesEvenements' => $mesEvenements,
            'search' => $search,
            'periode' => $period,
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
>>>>>>> isramedi
        ]);
    }

    #[Route('/nouveau', name: 'app_evenement_new')]
<<<<<<< HEAD
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
        $item = new Evenement();
=======
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function new(Request $request): Response
    {
        $item = new Evenement();
        $item->setOrganisateur($this->getUser());
        $item->setStatut(StatutEvenement::EN_ATTENTE);
>>>>>>> isramedi
        $form = $this->createForm(EvenementFormType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($item);
            $this->entityManager->flush();

<<<<<<< HEAD
            $this->addFlash('success', 'Événement créé avec succès.');
=======
            $this->addFlash('success', 'Événement créé. Il est en attente de validation par un administrateur.');
>>>>>>> isramedi
            return $this->redirectToRoute('app_evenement_index');
        }

        return $this->render('evenement/form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => false,
            'item' => $item,
        ]);
    }

<<<<<<< HEAD
    #[Route('/{id}', name: 'app_evenement_show')]
    public function show(Evenement $item): Response
    {
=======
    #[Route('/{id}', name: 'app_evenement_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function show(Evenement $item): Response
    {
        if ($item->getStatut() !== StatutEvenement::VALIDE && !$this->canManageEvent($item)) {
            throw $this->createAccessDeniedException('Cet événement n\'est pas encore publié.');
        }
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
>>>>>>> isramedi
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
    #[Route('/{id}/supprimer', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $item): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
=======
    #[Route('/{id}/supprimer', name: 'app_evenement_delete', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function delete(Request $request, Evenement $item): Response
    {
        if (!$this->canManageEvent($item)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet événement.');
        }
>>>>>>> isramedi
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($item);
            $this->entityManager->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('app_evenement_index');
    }

<<<<<<< HEAD
    // Participation endpoints
    #[Route('/{id}/participer', name: 'app_evenement_participer', methods: ['POST'])]
    public function participer(Request $request, Evenement $item): Response
    {
        if (! $this->isCsrfTokenValid('participate'.$item->getId(), $request->request->get('_token'))) {
=======
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
>>>>>>> isramedi
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $item->getId()]);
        }

        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $email = $request->request->get('email');

<<<<<<< HEAD
        if (! $firstName || ! $lastName || ! $email) {
=======
        if (!$firstName || !$lastName || !$email) {
>>>>>>> isramedi
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

<<<<<<< HEAD
    #[Route('/{id}/participer/ajax', name: 'app_evenement_participer_ajax', methods: ['POST'])]
=======
    #[Route('/{id}/participer/ajax', name: 'app_evenement_participer_ajax', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
>>>>>>> isramedi
    public function participerAjax(Request $request, Evenement $item): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $firstName = $payload['firstName'] ?? null;
        $lastName = $payload['lastName'] ?? null;
        $email = $payload['email'] ?? null;

<<<<<<< HEAD
        if (! $firstName || ! $lastName || ! $email) {
=======
        if (!$firstName || !$lastName || !$email) {
>>>>>>> isramedi
            return $this->json(['error' => 'Missing fields'], 400);
        }

        $participant = new Participant();
        $participant->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setEvenement($item);

        $this->entityManager->persist($participant);
        $this->entityManager->flush();

<<<<<<< HEAD
        return $this->json(['id' => $participant->getId(), 'firstName' => $participant->getFirstName(), 'lastName' => $participant->getLastName(), 'email' => $participant->getEmail()], 201);
    }

    // AJAX endpoints
=======
        return $this->json([
            'id' => $participant->getId(),
            'firstName' => $participant->getFirstName(),
            'lastName' => $participant->getLastName(),
            'email' => $participant->getEmail()
        ], 201);
    }

>>>>>>> isramedi
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
=======
    #[IsGranted('ROLE_ORGANISATEUR')]
>>>>>>> isramedi
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
}
