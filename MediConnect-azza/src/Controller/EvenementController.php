<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Participant;
use App\Form\EvenementFormType;
use App\Repository\EvenementRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        $items = $this->evenementRepository->findAll();

        return $this->render('evenement/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/nouveau', name: 'app_evenement_new')]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
        $item = new Evenement();
        $form = $this->createForm(EvenementFormType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($item);
            $this->entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès.');
            return $this->redirectToRoute('app_evenement_index');
        }

        return $this->render('evenement/form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => false,
            'item' => $item,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_show')]
    public function show(Evenement $item): Response
    {
        $participants = $this->participantRepository->findByEvenement($item);
        return $this->render('evenement/show.html.twig', ['item' => $item, 'participants' => $participants]);
    }

    #[Route('/{id}/modifier', name: 'app_evenement_edit')]
    public function edit(Request $request, Evenement $item): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
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

    #[Route('/{id}/supprimer', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $item): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($item);
            $this->entityManager->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('app_evenement_index');
    }

    // Participation endpoints
    #[Route('/{id}/participer', name: 'app_evenement_participer', methods: ['POST'])]
    public function participer(Request $request, Evenement $item): Response
    {
        if (! $this->isCsrfTokenValid('participate'.$item->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $item->getId()]);
        }

        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $email = $request->request->get('email');

        if (! $firstName || ! $lastName || ! $email) {
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

    #[Route('/{id}/participer/ajax', name: 'app_evenement_participer_ajax', methods: ['POST'])]
    public function participerAjax(Request $request, Evenement $item): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $firstName = $payload['firstName'] ?? null;
        $lastName = $payload['lastName'] ?? null;
        $email = $payload['email'] ?? null;

        if (! $firstName || ! $lastName || ! $email) {
            return $this->json(['error' => 'Missing fields'], 400);
        }

        $participant = new Participant();
        $participant->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setEvenement($item);

        $this->entityManager->persist($participant);
        $this->entityManager->flush();

        return $this->json(['id' => $participant->getId(), 'firstName' => $participant->getFirstName(), 'lastName' => $participant->getLastName(), 'email' => $participant->getEmail()], 201);
    }

    // AJAX endpoints
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
