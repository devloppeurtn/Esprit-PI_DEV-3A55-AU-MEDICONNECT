<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\StatutInvitation;
use App\Form\ResetPasswordFormType;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InvitationController extends AbstractController
{
    public function __construct(
        private InvitationRepository $invitationRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/invitation/{token}/accepter', name: 'app_invitation_accepter', methods: ['GET', 'POST'])]
    public function accepter(Request $request, string $token): Response
    {
        $invitation = $this->invitationRepository->findOneByToken($token);

        if (!$invitation || $invitation->getStatut() !== StatutInvitation::EN_ATTENTE) {
            $this->addFlash('error', 'Cette invitation n\'est plus valide ou a déjà été traitée.');
            return $this->redirectToRoute('app_login');
        }

        $secretaire = $invitation->getSecretaire();
        $medecin = $invitation->getMedecin();

        $doitDefinirMotDePasse = (bool) $secretaire->getResetToken();

        $form = null;
        if ($doitDefinirMotDePasse) {
            $form = $this->createForm(ResetPasswordFormType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $secretaire->setPassword($this->passwordHasher->hashPassword($secretaire, $form->get('password')->getData()));
                $secretaire->setResetToken(null);
                $secretaire->setResetTokenExpiresAt(null);
                $secretaire->setMedecin($medecin);
                $invitation->setStatut(StatutInvitation::ACCEPTE);
                $this->entityManager->flush();

                $this->sendNotificationMedecin($medecin, $secretaire, accepted: true);
                $this->addFlash('success', 'Invitation acceptée ! Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        } else {
            if ($request->isMethod('POST')) {
                $secretaire->setMedecin($medecin);
                $invitation->setStatut(StatutInvitation::ACCEPTE);
                $this->entityManager->flush();

                $this->sendNotificationMedecin($medecin, $secretaire, accepted: true);
                $this->addFlash('success', 'Invitation acceptée ! Vous pouvez vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('invitation/accepter.html.twig', [
            'invitation' => $invitation,
            'form' => $form,
            'doitDefinirMotDePasse' => $doitDefinirMotDePasse,
        ]);
    }

    #[Route('/invitation/{token}/refuser', name: 'app_invitation_refuser', methods: ['GET'])]
    public function refuser(string $token): Response
    {
        $invitation = $this->invitationRepository->findOneByToken($token);

        if (!$invitation || $invitation->getStatut() !== StatutInvitation::EN_ATTENTE) {
            $this->addFlash('error', 'Cette invitation n\'est plus valide ou a déjà été traitée.');
            return $this->redirectToRoute('app_login');
        }

        $secretaire = $invitation->getSecretaire();
        $medecin = $invitation->getMedecin();

        $invitation->setStatut(StatutInvitation::REFUSE);
        $this->entityManager->flush();

        $this->sendNotificationMedecin($medecin, $secretaire, accepted: false);
        $this->addFlash('info', 'Vous avez refusé l\'invitation.');
        return $this->redirectToRoute('app_login');
    }

    private function sendNotificationMedecin($medecin, $secretaire, bool $accepted): void
    {
        $fromAddress = $_ENV['MAILER_FROM'] ?? getenv('MAILER_FROM') ?: 'MediConnect <noreply@mediconnect.com>';
        $template = $accepted ? 'emails/medecin_invitation_acceptee.html.twig' : 'emails/medecin_invitation_refusee.html.twig';
        $subject = $accepted
            ? $secretaire->getNomComplet() . ' a accepté votre invitation - MediConnect'
            : $secretaire->getNomComplet() . ' a refusé votre invitation - MediConnect';

        $emailMessage = (new TemplatedEmail())
            ->from(Address::create($fromAddress))
            ->to($medecin->getEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'medecin' => $medecin,
                'secretaire' => $secretaire,
            ]);

        try {
            $this->mailer->send($emailMessage);
        } catch (TransportExceptionInterface $e) {
            error_log('[MediConnect] Erreur envoi email notification médecin: ' . $e->getMessage());
        }
    }
}
