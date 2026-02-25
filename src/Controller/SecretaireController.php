<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\Secretaire;
use App\Entity\StatutRendezVous;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    ) {
    }

    #[Route('', name: 'app_secretaire_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var Secretaire $secretaire */
        $secretaire = $this->getUser();
        if (!$secretaire instanceof Secretaire) {
            return $this->redirectToRoute('app_profile');
        }

        $medecin = $secretaire->getMedecin();
        $invitations = $secretaire->getInvitations();
        $invitationsEnAttente = $invitations->filter(fn ($i) => $i->getStatut()->value === 'EN_ATTENTE');
        $invitationsAcceptees = $invitations->filter(fn ($i) => $i->getStatut()->value === 'ACCEPTEE');
        $invitationsRefusees = $invitations->filter(fn ($i) => $i->getStatut()->value === 'REFUSEE');

        return $this->render('secretaire/index.html.twig', [
            'secretaire' => $secretaire,
            'medecin' => $medecin,
            'invitationsEnAttente' => $invitationsEnAttente,
            'invitationsAcceptees' => $invitationsAcceptees,
            'invitationsRefusees' => $invitationsRefusees,
        ]);
    }

    #[Route('/rendez-vous', name: 'app_secretaire_rendez_vous', methods: ['GET'])]
    public function rendezVous(): Response
    {
        /** @var Secretaire $secretaire */
        $secretaire = $this->getUser();
        if (!$secretaire instanceof Secretaire) {
            return $this->redirectToRoute('app_profile');
        }

        $medecin = $secretaire->getMedecin();
        if (!$medecin) {
            $this->addFlash('warning', 'Vous n\'êtes associé à aucun médecin.');
            return $this->render('secretaire/rendez_vous.html.twig', [
                'secretaire' => $secretaire,
                'rdvsEnAttente' => [],
                'rdvsConfirmes' => [],
            ]);
        }

        $rdvsEnAttente = $this->rdvRepo->findEnAttenteByMedecin($medecin);
        $rdvsConfirmes = array_filter(
            $this->rdvRepo->findByMedecin($medecin),
            fn (RendezVous $r) => $r->getStatut() === StatutRendezVous::CONFIRME
        );

        return $this->render('secretaire/rendez_vous.html.twig', [
            'secretaire' => $secretaire,
            'rdvsEnAttente' => $rdvsEnAttente,
            'rdvsConfirmes' => $rdvsConfirmes,
        ]);
    }

    #[Route('/rendez-vous/{id}/valider', name: 'app_secretaire_rdv_valider', methods: ['POST'])]
    public function validerRdv(Request $request, RendezVous $rdv): Response
    {
        /** @var Secretaire $secretaire */
        $secretaire = $this->getUser();
        if (!$secretaire instanceof Secretaire || $secretaire->getMedecin() !== $rdv->getMedecin()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_secretaire_rendez_vous');
        }

        if ($rdv->getStatut() !== StatutRendezVous::EN_ATTENTE) {
            $this->addFlash('error', 'Ce rendez-vous n\'est plus en attente.');
            return $this->redirectToRoute('app_secretaire_rendez_vous');
        }

        $token = $request->request->get('_token');
        if (!$token || !is_string($token) || !$this->isCsrfTokenValid('valider_rdv_' . $rdv->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_secretaire_rendez_vous');
        }

        $rdv->setStatut(StatutRendezVous::CONFIRME);
        $this->em->flush();
        $this->addFlash('success', 'Rendez-vous confirmé.');
        return $this->redirectToRoute('app_secretaire_rendez_vous');
    }

    #[Route('/rendez-vous/{id}/refuser', name: 'app_secretaire_rdv_refuser', methods: ['POST'])]
    public function refuserRdv(Request $request, RendezVous $rdv): Response
    {
        /** @var Secretaire $secretaire */
        $secretaire = $this->getUser();
        if (!$secretaire instanceof Secretaire || $secretaire->getMedecin() !== $rdv->getMedecin()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_secretaire_rendez_vous');
        }

        if ($rdv->getStatut() !== StatutRendezVous::EN_ATTENTE) {
            $this->addFlash('error', 'Ce rendez-vous n\'est plus en attente.');
            return $this->redirectToRoute('app_secretaire_rendez_vous');
        }

        $token = $request->request->get('_token');
        if (!$token || !is_string($token) || !$this->isCsrfTokenValid('refuser_rdv_' . $rdv->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_secretaire_rendez_vous');
        }

        $rdv->setStatut(StatutRendezVous::ANNULE);
        $this->em->flush();
        $this->addFlash('success', 'Rendez-vous refusé.');
        return $this->redirectToRoute('app_secretaire_rendez_vous');
    }
}
