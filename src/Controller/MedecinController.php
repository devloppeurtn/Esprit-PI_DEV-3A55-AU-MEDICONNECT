<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\DossierMedical;
use App\Entity\Invitation;
use App\Entity\Medecin;
use App\Entity\Ordonnance;
use App\Entity\Patient;
use App\Entity\RapportMedical;
use App\Entity\RendezVous;
use App\Entity\Secretaire;
use App\Entity\StatutInvitation;
use App\Entity\StatutRendezVous;
use App\Entity\Utilisateur;
use App\Form\AjouterSecretaireFormType;
use App\Form\ConsultationFormType;
use App\Form\OrdonnanceFormType;
use App\Form\RapportMedicalFormType;
use App\Repository\RendezVousRepository;
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
    ) {
    }

    #[Route('', name: 'app_medecin_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin) {
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('medecin/index.html.twig', [
            'medecin' => $medecin,
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
            $email = trim((string) $form->get('email')->getData());
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
                $this->sendInvitationSecretaire($invitation, definirMotDePasse: false);
                $this->addFlash('success', 'Invitation envoyée. Le secrétaire recevra un email pour accepter ou refuser.');
            } elseif ($existing !== null) {
                $this->addFlash('error', 'Un compte existe déjà avec cet email mais n\'est pas un secrétaire.');
                return $this->redirectToRoute('app_medecin_secretaires');
            } else {
                $nom = explode('@', $email)[0] ?? 'Secrétaire';
                $secretaire = new Secretaire();
                $secretaire->setEmail($email);
                $secretaire->setNomComplet(ucfirst($nom));
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
                $this->sendInvitationSecretaire($invitation, definirMotDePasse: true);
                $this->addFlash('success', "Invitation envoyée. Le secrétaire recevra un email pour accepter (et définir son mot de passe) ou refuser.");
            }

            return $this->redirectToRoute('app_medecin_secretaires');
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
            $this->addFlash('success', 'Consultation créée. Ajoutez une ordonnance et un rapport médical.');
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
        $isOwnConsultation = $consultation->getMedecin() === $medecin;
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
        if (!$medecin instanceof Medecin || $consultation->getMedecin() !== $medecin) {
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
        if (!$medecin instanceof Medecin || $consultation->getMedecin() !== $medecin) {
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
        if (!$medecin instanceof Medecin || $consultation->getMedecin() !== $medecin) {
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
        if (!$medecin instanceof Medecin || !$consultation || $consultation->getMedecin() !== $medecin) {
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
        if (!$medecin instanceof Medecin || !$consultation || $consultation->getMedecin() !== $medecin) {
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

    #[Route('/consultation/{id}/rapport', name: 'app_medecin_rapport_ajouter', methods: ['GET', 'POST'])]
    public function ajouterRapport(Request $request, Consultation $consultation): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $consultation->getMedecin() !== $medecin) {
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
        if (!$medecin instanceof Medecin || !$consultation || $consultation->getMedecin() !== $medecin) {
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
        if (!$medecin instanceof Medecin || !$consultation || $consultation->getMedecin() !== $medecin) {
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
        if (!$medecin instanceof Medecin || $rdv->getMedecin() !== $medecin) {
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
        if (!$medecin instanceof Medecin || $secretaire->getMedecin() !== $medecin) {
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
            ->subject('Invitation à rejoindre l\'équipe de ' . $medecin->getNomComplet() . ' - MediConnect')
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
            error_log('[MediConnect] Erreur envoi email invitation secrétaire: ' . $e->getMessage());
        }
    }
}
