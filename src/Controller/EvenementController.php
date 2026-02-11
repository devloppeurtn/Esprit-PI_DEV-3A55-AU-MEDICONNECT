<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Participant;
use App\Enum\StatutEvenement;
use App\Form\EvenementFormType;
use App\Repository\EvenementRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function index(): Response
    {
        $items = $this->evenementRepository->findValides();
        $mesEvenements = [];
        if ($this->isGranted('ROLE_ORGANISATEUR')) {
            $mesEvenements = $this->evenementRepository->findByOrganisateur($this->getUser());
        }

        return $this->render('evenement/index.html.twig', [
            'items' => $items,
            'mesEvenements' => $mesEvenements,
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
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function new(Request $request): Response
    {
        $item = new Evenement();
        $item->setOrganisateur($this->getUser());
        $item->setStatut(StatutEvenement::EN_ATTENTE);
        $form = $this->createForm(EvenementFormType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        return $this->render('evenement/show.html.twig', ['item' => $item, 'participants' => $participants]);
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
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
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
        if ($this->isGranted('ROLE_ORGANISATEUR') && $item->getOrganisateur() === $this->getUser()) {
            return true;
        }
        return false;
    }

    #[Route('/{id}/participer', name: 'app_evenement_participer', methods: ['POST'], requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function participer(Request $request, Evenement $item): Response
    {
        if (!$this->isCsrfTokenValid('participate'.$item->getId(), $request->request->get('_token'))) {
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
        $payload = json_decode($request->getContent(), true);
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
            'email' => $participant->getEmail()
        ], 201);
    }

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
    #[IsGranted('ROLE_ORGANISATEUR')]
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
