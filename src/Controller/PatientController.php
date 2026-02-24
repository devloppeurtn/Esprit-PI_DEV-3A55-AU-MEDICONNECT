<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\DossierMedical;
use App\Entity\DocumentPatient;
use App\Entity\MedicamentActuel;
use App\Entity\Ordonnance;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Form\DocumentPatientFormType;
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/patient')]
#[IsGranted('ROLE_PATIENT')]
class PatientController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RendezVousRepository $rdvRepo,
        private MedecinRepository $medecinRepo,
        private SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'app_patient_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $rdvs = $this->rdvRepo->findByPatient($patient);

        return $this->render('patient/index.html.twig', [
            'patient' => $patient,
            'rdvs' => $rdvs,
        ]);
    }

    #[Route('/dossier-medical', name: 'app_patient_dossier', methods: ['GET', 'POST'])]
    public function dossierMedical(Request $request): Response
    {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $dossier = $patient->getDossierMedical();
        if (!$dossier) {
            $dossier = new DossierMedical();
            $dossier->setPatient($patient);
            $patient->setDossierMedical($dossier);
            $this->em->persist($dossier);
            $this->em->flush();
        }

        $document = new DocumentPatient();
        $document->setAjouteParPatient(true);
        $form = $this->createForm(DocumentPatientFormType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichier = $form->get('fichier')->getData();
            if ($fichier) {
                $originalFilename = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                try {
                    $extension = $fichier->guessExtension();
                } catch (\Throwable) {
                    $extension = null;
                }
                if (!$extension) {
                    $extension = $fichier->getClientOriginalExtension() ?: 'bin';
                }
                $extension = preg_replace('/[^a-zA-Z0-9]/', '', (string) $extension) ?: 'bin';

                $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
                try {
                    $fichier->move(
                        $this->getParameter('documents_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
                    return $this->redirectToRoute('app_patient_dossier');
                }
                $document->setNomFichier($fichier->getClientOriginalName());
                $document->setCheminFichier($newFilename);
            }
            $document->setDossierMedical($dossier);
            $dossier->addDocument($document);
            $this->em->persist($document);
            $this->em->flush();
            $this->addFlash('success', 'Document ajouté.');
            return $this->redirectToRoute('app_patient_dossier');
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Upload refuse. Verifiez le type de document, la taille (max 25 Mo) et le format (PDF/JPG/PNG/GIF).');
        }

        $consultations = $dossier->getConsultations()->toArray();
        usort($consultations, fn (Consultation $a, Consultation $b) => $b->getDate() <=> $a->getDate());

        return $this->render('patient/dossier.html.twig', [
            'patient' => $patient,
            'dossier' => $dossier,
            'form' => $form,
            'consultations' => $consultations,
        ]);
    }

    #[Route('/dossier-medical/consultation/{id}', name: 'app_patient_consultation_voir', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function consultationVoir(Consultation $consultation): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $dossier = $patient->getDossierMedical();
        if (!$dossier || $consultation->getDossierMedical() !== $dossier) {
            $this->addFlash('error', 'Consultation introuvable.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        return $this->render('patient/consultation_voir.html.twig', [
            'patient' => $patient,
            'consultation' => $consultation,
        ]);
    }

    #[Route('/dossier-medical/consultation/{id}/pdf', name: 'app_patient_consultation_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadConsultationPdf(Consultation $consultation): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $dossier = $patient->getDossierMedical();
        if (!$dossier) {
            throw $this->createNotFoundException('Dossier médical introuvable.');
        }

                if (!$dossier || !$consultation || $consultation->getDossierMedical() !== $dossier) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette ordonnance.');
        }

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('medecin/prescription_pdf.html.twig', [
            'ordonnances' => $consultation->getOrdonnances(),
            'consultation' => $consultation,
            'medecin' => $consultation->getMedecin(),
            'patient' => $patient,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        $filename = 'ordonnance_consultation_' . $consultation->getId() . '.pdf';

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/dossier-medical/ordonnance/{id}/ajouter-medicament', name: 'app_patient_medicament_ajouter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function medicamentAjouter(Request $request, Ordonnance $ordonnance): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $dossier = $patient->getDossierMedical();
        if (!$dossier) {
            $this->addFlash('error', 'Dossier médical introuvable.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        if (!$dossier || $ordonnance->getConsultation()?->getDossierMedical() !== $dossier) {
            $this->addFlash('error', 'Ordonnance introuvable.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $token = $request->request->get('_token');
        if (!$token || !$this->isCsrfTokenValid('ajouter_medicament_' . $ordonnance->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $ma = new MedicamentActuel();
        $ma->setMedicament($ordonnance->getMedicament() ?? 'Médicament');
        $ma->setMethodeUtilisation($ordonnance->getMethodeUtilisation());
        $ma->setOrdonnance($ordonnance);
        $ma->setDossierMedical($dossier);
        $dossier->addMedicamentActuel($ma);
        $this->em->persist($ma);
        $this->em->flush();
        $this->addFlash('success', 'Médicament ajouté à votre liste actuelle.');
        return $this->redirect($request->headers->get('Referer', $this->generateUrl('app_patient_dossier')));
    }

    #[Route('/dossier-medical/medicament-actuel/{id}/supprimer', name: 'app_patient_medicament_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function medicamentSupprimer(Request $request, MedicamentActuel $medicamentActuel): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $dossier = $patient->getDossierMedical();
        if (!$dossier || $medicamentActuel->getDossierMedical() !== $dossier) {
            $this->addFlash('error', 'Médicament introuvable.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $token = $request->request->get('_token');
        if (!$token || !$this->isCsrfTokenValid('supprimer_medicament_' . $medicamentActuel->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $dossier->removeMedicamentActuel($medicamentActuel);
        $this->em->remove($medicamentActuel);
        $this->em->flush();
        $this->addFlash('success', 'Médicament retiré de votre liste.');
        return $this->redirectToRoute('app_patient_dossier');
    }

    #[Route('/prendre-rendez-vous', name: 'app_patient_prendre_rdv', methods: ['GET', 'POST'])]
    public function prendreRendezVous(Request $request): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $medecinId = $request->query->getInt('medecin');
        if (!$medecinId) {
            return $this->redirectToRoute('app_patient_medecins');
        }
        $medecinPreselectionne = $this->medecinRepo->find($medecinId);
        if (!$medecinPreselectionne) {
            return $this->redirectToRoute('app_patient_medecins');
        }

        return $this->render('patient/prendre_rdv.html.twig', [
            'patient' => $patient,
            'medecin' => $medecinPreselectionne,
        ]);
    }

    #[Route('/medecins', name: 'app_patient_medecins', methods: ['GET'])]
    public function medecins(Request $request): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $nom = $request->query->get('nom', '');
        $specialite = $request->query->get('specialite', '');
        $tri = $request->query->get('tri', 'az');
        if (!in_array($tri, ['az', 'za'], true)) {
            $tri = 'az';
        }

        $medecins = $this->medecinRepo->findMedecinsActifsAvecFiltres(
            $nom === '' ? null : $nom,
            $specialite === '' ? null : $specialite,
            $tri
        );
        $specialites = $this->medecinRepo->findSpecialitesDistinctes();
        $staffImages = ['staff-1', 'staff-2', 'staff-3', 'staff-4', 'staff-5', 'staff-6', 'staff-7', 'staff-8', 'staff-10', 'staff-11', 'staff-14'];

        if ($request->isXmlHttpRequest()) {
            if ($this->container->has('profiler')) {
                $this->container->get('profiler')->disable();
            }

            return $this->render('patient/_medecins_list.html.twig', [
                'medecins' => $medecins,
                'staffImages' => $staffImages,
            ]);
        }

        return $this->render('patient/medecins.html.twig', [
            'patient' => $patient,
            'medecins' => $medecins,
            'specialites' => $specialites,
            'staffImages' => $staffImages,
            'filtres' => ['nom' => $nom, 'specialite' => $specialite, 'tri' => $tri],
        ]);
    }

    #[Route('/rendez-vous/{id}/annuler', name: 'app_patient_rdv_annuler', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function annulerRdv(Request $request, RendezVous $rdv): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        if ($rdv->getPatient() !== $patient) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_patient_rendez_vous');
        }
        if ($rdv->getStatut() === \App\Entity\StatutRendezVous::ANNULE) {
            $this->addFlash('warning', 'Ce rendez-vous est déjà annulé.');
            return $this->redirectToRoute('app_patient_rendez_vous');
        }
        if ($rdv->getConsultation()) {
            $this->addFlash('error', 'Impossible d\'annuler : une consultation existe déjà.');
            return $this->redirectToRoute('app_patient_rendez_vous');
        }
        $token = $request->request->get('_token');
        if (!$token || !$this->isCsrfTokenValid('annuler_rdv_' . $rdv->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_patient_rendez_vous');
        }
        $rdv->setStatut(\App\Entity\StatutRendezVous::ANNULE);
        $this->em->flush();
        $this->addFlash('success', 'Rendez-vous annulé.');
        return $this->redirectToRoute('app_patient_rendez_vous');
    }

    #[Route('/rendez-vous', name: 'app_patient_rendez_vous', methods: ['GET'])]
    public function rendezVous(): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $rdvs = $this->rdvRepo->findByPatient($patient);

        return $this->render('patient/rendez_vous.html.twig', [
            'patient' => $patient,
            'rdvs' => $rdvs,
        ]);
    }

    #[Route('/documents', name: 'app_patient_documents', methods: ['GET'])]
    public function documents(): Response
    {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        return $this->redirectToRoute('app_patient_dossier');
    }

    #[Route('/documents/{id}/supprimer', name: 'app_patient_document_supprimer', methods: ['POST'])]
    public function supprimerDocument(Request $request, DocumentPatient $document): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $dossier = $patient->getDossierMedical();
        if (!$dossier || $document->getDossierMedical() !== $dossier) {
            $this->addFlash('error', 'Document non trouvé.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        if (!$document->isAjouteParPatient()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que les documents que vous avez ajoutés.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_document_' . $document->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $chemin = $this->getParameter('documents_directory') . '/' . $document->getCheminFichier();
        if (file_exists($chemin)) {
            unlink($chemin);
        }
        $this->em->remove($document);
        $this->em->flush();
        $this->addFlash('success', 'Document supprimé.');
        return $this->redirectToRoute('app_patient_dossier');
    }

    #[Route('/documents/{id}/voir', name: 'app_patient_document_voir', methods: ['GET'])]
    public function voirDocument(DocumentPatient $document): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $dossier = $patient->getDossierMedical();
        if (!$dossier || $document->getDossierMedical() !== $dossier) {
            $this->addFlash('error', 'Document non trouvé.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $storedName = trim((string) $document->getCheminFichier());
        if ($storedName === '') {
            $this->addFlash('error', 'Fichier introuvable.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $safeStoredName = basename(str_replace('\\', '/', $storedName));
        $absolutePath = rtrim((string) $this->getParameter('documents_directory'), '/\\') . DIRECTORY_SEPARATOR . $safeStoredName;
        if (!is_file($absolutePath)) {
            $this->addFlash('error', 'Le fichier est manquant sur le serveur.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $downloadName = $document->getNomFichier() ?: $safeStoredName;

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $downloadName);
        $response->headers->set('Content-Type', 'application/octet-stream');

        return $response;
    }

    #[Route('/rendez-vous/ajax', name: 'app_patient_rdv_ajax', methods: ['POST'])]
    public function rendezVousAjax(Request $request): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $token = $request->request->get('_token');
        if (!$token || !$this->isCsrfTokenValid('rdv_ajax', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token invalide.'], 400);
        }

        $medecinId = $request->request->get('medecin');
        $dateDebut = $request->request->get('dateDebut');
        $noteRaw = $request->request->get('note', '');

        if ($medecinId === null || $medecinId === '' || !is_numeric($medecinId)) {
            return new JsonResponse(['success' => false, 'message' => 'Médecin requis.'], 400);
        }
        $medecinId = (int) $medecinId;
        if ($medecinId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Médecin invalide.'], 400);
        }

        if ($dateDebut === null || $dateDebut === '' || !is_string($dateDebut)) {
            return new JsonResponse(['success' => false, 'message' => 'Date et heure requises.'], 400);
        }
        $dateDebut = trim($dateDebut);
        if ($dateDebut === '') {
            return new JsonResponse(['success' => false, 'message' => 'Date et heure requises.'], 400);
        }

        $note = is_string($noteRaw) ? strip_tags(trim($noteRaw)) : '';
        if (mb_strlen($note) > 2000) {
            return new JsonResponse(['success' => false, 'message' => 'La note ne doit pas dépasser 2000 caractères.'], 400);
        }
        $note = $note !== '' ? $note : null;

        $medecin = $this->medecinRepo->find($medecinId);
        if (!$medecin || $medecin->getStatut() !== \App\Entity\StatutCompte::ACTIF) {
            return new JsonResponse(['success' => false, 'message' => 'Médecin invalide.'], 400);
        }

        try {
            $date = new \DateTime($dateDebut);
            if ($date <= new \DateTime()) {
                return new JsonResponse(['success' => false, 'message' => 'La date doit être dans le futur.'], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Date invalide.'], 400);
        }

        $rdv = new RendezVous();
        $rdv->setPatient($patient);
        $rdv->setMedecin($medecin);
        $rdv->setDateDebut($date);
        $rdv->setDateFin((clone $date)->modify('+30 minutes'));
        $rdv->setNote($note);
        $medecin->addRendezVous($rdv);
        $this->em->persist($rdv);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Demande de rendez-vous envoyée. La secrétaire du médecin la validera sous peu.',
        ]);
    }

    //  CHATBOT API 

    #[Route('/chatbot/medecins', name: 'app_patient_chatbot_medecins', methods: ['GET'])]
    public function chatbotMedecins(Request $request): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $specialite = $request->query->get('specialite', '');
        $nom        = $request->query->get('nom', '');
        $specialite = is_string($specialite) ? trim($specialite) : '';
        $nom        = is_string($nom)        ? trim($nom)        : '';

        $medecins = $this->medecinRepo->findMedecinsActifsAvecFiltres(
            $nom        !== '' ? $nom        : null,
            $specialite !== '' ? $specialite : null,
            'az'
        );

        $data = array_map(static fn (\App\Entity\Medecin $m) => [
            'id'         => $m->getId(),
            'nom'        => $m->getNomComplet(),
            'specialite' => $m->getSpecialite() ?? 'Médecin généraliste',
        ], $medecins);

        $specialites = $this->medecinRepo->findSpecialitesDistinctes();

        return new JsonResponse([
            'success'     => true,
            'medecins'    => $data,
            'specialites' => $specialites,
        ]);
    }

    #[Route('/chatbot/rdv', name: 'app_patient_chatbot_rdv', methods: ['POST'])]
    public function chatbotRdv(Request $request): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->request->all();
        }

        $medecinId = isset($data['medecinId']) ? (int) $data['medecinId'] : 0;
        $dateDebut = isset($data['dateDebut']) && is_string($data['dateDebut']) ? trim($data['dateDebut']) : '';

        if ($medecinId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Médecin requis.'], 400);
        }
        if ($dateDebut === '') {
            return new JsonResponse(['success' => false, 'message' => 'Date et heure requises.'], 400);
        }

        $medecin = $this->medecinRepo->find($medecinId);
        if (!$medecin || $medecin->getStatut() !== \App\Entity\StatutCompte::ACTIF) {
            return new JsonResponse(['success' => false, 'message' => 'Médecin introuvable.'], 400);
        }

        try {
            $date = new \DateTime($dateDebut);
            if ($date <= new \DateTime()) {
                return new JsonResponse(['success' => false, 'message' => 'La date doit être dans le futur.'], 400);
            }
        } catch (\Exception) {
            return new JsonResponse(['success' => false, 'message' => 'Date invalide.'], 400);
        }

        $rdv = new RendezVous();
        $rdv->setPatient($patient);
        $rdv->setMedecin($medecin);
        $rdv->setDateDebut($date);
        $rdv->setDateFin((clone $date)->modify('+30 minutes'));
        $rdv->setNote('Demande via chatbot');
        $medecin->addRendezVous($rdv);
        $this->em->persist($rdv);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre demande de rendez-vous a bien été envoyée ! La secrétaire du Dr ' . $medecin->getNomComplet() . ' la validera sous peu.',
        ]);
    }
}