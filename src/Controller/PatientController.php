<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\DossierMedical;
use App\Entity\DocumentPatient;
use App\Entity\MedicamentActuel;
use App\Entity\Notification;
use App\Entity\Ordonnance;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\StatutRendezVous;
use App\Repository\NotificationRepository;
use App\Form\DocumentPatientFormType;
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use App\Service\ChatbotAiService;
use App\Service\OcrSpaceService;
use App\Service\RendezVousBookingValidator;
use App\Service\RendezVousNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        private ChatbotAiService $chatbotAiService,
        private RendezVousBookingValidator $rdvBookingValidator,
        private RendezVousNotificationService $rdvNotificationService,
        private NotificationRepository $notificationRepo,
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
        $notifications = $this->notificationRepo->findLatestByUser($patient, 6);

        return $this->render('patient/index.html.twig', [
            'patient' => $patient,
            'rdvs' => $rdvs,
            'notifications' => $notifications,
        ]);
    }

    #[Route('/dossier-medical', name: 'app_patient_dossier', methods: ['GET', 'POST'])]
    public function dossierMedical(Request $request, OcrSpaceService $ocrService): Response
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
            $this->addFlash('success', 'Document ajoute.');
            return $this->redirectToRoute('app_patient_dossier');
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Upload refuse. Verifiez le type de document, la taille (max 25 Mo) et le format (PDF/DOC/DOCX/JPG/PNG/GIF/WEBP).');
        }

        $consultations = $dossier->getConsultations()->toArray();
        usort($consultations, fn (Consultation $a, Consultation $b) => $b->getDate() <=> $a->getDate());

        return $this->render('patient/dossier.html.twig', [
            'patient' => $patient,
            'dossier' => $dossier,
            'form' => $form,
            'consultations' => $consultations,
            'ocrConfigured' => $ocrService->isConfigured(),
        ]);
    }

    #[Route('/documents/scanner', name: 'app_patient_document_scanner', methods: ['POST'])]
    public function scannerDocument(Request $request, OcrSpaceService $ocrService): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $dossier = $patient->getDossierMedical();
        if (!$dossier) {
            $this->addFlash('error', 'Dossier medical introuvable.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('scan_document', $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        if (!$ocrService->isConfigured()) {
            $this->addFlash('warning', 'OCR.Space non configure. Ajoutez OCR_SPACE_API_KEY dans .env.local (ou activez la cle demo).');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $file = $request->files->get('scan_image');
        if (!$file instanceof UploadedFile) {
            $this->addFlash('error', 'Veuillez selectionner une image a scanner.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff', 'image/x-tiff'];
        $mimeType = $file->getMimeType();
        if (!is_string($mimeType) || !in_array($mimeType, $allowedMimeTypes, true)) {
            $this->addFlash('error', 'Format image non supporte. Utilisez JPG, PNG, GIF, WEBP, BMP ou TIFF.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        if ($file->getSize() !== null && $file->getSize() > 10 * 1024 * 1024) {
            $this->addFlash('error', 'Image trop volumineuse (max 10 Mo).');
            return $this->redirectToRoute('app_patient_dossier');
        }

        try {
            $extractedText = $ocrService->extractTextFromImage($file->getPathname());
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_patient_dossier');
        }

        if ($extractedText === '') {
            $this->addFlash('warning', 'Aucun texte detecte dans l\'image.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = (string) $this->slugger->slug($originalFilename !== '' ? $originalFilename : 'scan');
        if ($safeBase === '') {
            $safeBase = 'scan';
        }

        $pdfFilename = sprintf('ocr-%s-%s.pdf', $safeBase, uniqid());
        $documentsDir = rtrim((string) $this->getParameter('documents_directory'), '/\\');
        if (!is_dir($documentsDir)) {
            @mkdir($documentsDir, 0775, true);
        }
        $absolutePath = $documentsDir . DIRECTORY_SEPARATOR . $pdfFilename;

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', false);

        $escapedText = htmlspecialchars($extractedText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<html><head><meta charset="UTF-8"><style>body{font-family:\"DejaVu Sans\",Arial,sans-serif;font-size:12px;line-height:1.5;margin:24px;}h1{font-size:16px;margin:0 0 12px 0;} .content{white-space:pre-wrap;}</style></head><body><h1>Texte extrait (OCR.Space)</h1><div class="content">' . $escapedText . '</div></body></html>';

        try {
            $dompdf = new Dompdf($pdfOptions);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfBinary = $dompdf->output();
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible de generer le PDF OCR.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        if (@file_put_contents($absolutePath, $pdfBinary) === false) {
            $this->addFlash('error', 'Impossible d\'enregistrer le PDF OCR.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        $typeDocument = (string) $request->request->get('scan_type', 'analyse');
        $allowedTypes = ['analyse', 'radio', 'certificat', 'autre'];
        if (!in_array($typeDocument, $allowedTypes, true)) {
            $typeDocument = 'analyse';
        }

        $description = trim((string) $request->request->get('scan_description', ''));
        $prefix = 'Texte extrait automatiquement (OCR.Space).';
        if ($description === '') {
            $description = $prefix;
        } else {
            $description = $prefix . ' ' . $description;
        }

        $document = (new DocumentPatient())
            ->setAjouteParPatient(true)
            ->setNomFichier(sprintf('OCR_%s.pdf', $safeBase))
            ->setCheminFichier($pdfFilename)
            ->setTypeDocument($typeDocument)
            ->setDescription($description)
            ->setDossierMedical($dossier);

        $dossier->addDocument($document);
        $this->em->persist($document);
        $this->em->flush();

        $this->addFlash('success', 'Scan termine. Le PDF OCR a ete ajoute a vos documents.');
        return $this->redirectToRoute('app_patient_dossier');
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
            throw $this->createNotFoundException('Dossier mÃ©dical introuvable.');
        }

                if (!$dossier || !$consultation || $consultation->getDossierMedical() !== $dossier) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accÃ¨s Ã  cette ordonnance.');
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
            $this->addFlash('error', 'Dossier mÃ©dical introuvable.');
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
        $ma->setMedicament($ordonnance->getMedicament() ?? 'MÃ©dicament');
        $ma->setMethodeUtilisation($ordonnance->getMethodeUtilisation());
        $ma->setOrdonnance($ordonnance);
        $ma->setDossierMedical($dossier);
        $dossier->addMedicamentActuel($ma);
        $this->em->persist($ma);
        $this->em->flush();
        $this->addFlash('success', 'MÃ©dicament ajoutÃ© Ã  votre liste actuelle.');
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
            $this->addFlash('error', 'MÃ©dicament introuvable.');
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
        $this->addFlash('success', 'MÃ©dicament retirÃ© de votre liste.');
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
            $this->addFlash('error', 'Action non autorisÃ©e.');
            return $this->redirectToRoute('app_patient_rendez_vous');
        }
        if ($rdv->getStatut() === StatutRendezVous::ANNULE) {
            $this->addFlash('warning', 'Ce rendez-vous est dÃ©jÃ  annulÃ©.');
            return $this->redirectToRoute('app_patient_rendez_vous');
        }
        if ($rdv->getConsultation()) {
            $this->addFlash('error', 'Impossible d\'annuler : une consultation existe dÃ©jÃ .');
            return $this->redirectToRoute('app_patient_rendez_vous');
        }
        $token = $request->request->get('_token');
        if (!$token || !$this->isCsrfTokenValid('annuler_rdv_' . $rdv->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_patient_rendez_vous');
        }
        $rdv->setStatut(StatutRendezVous::ANNULE);
        $this->em->flush();
        $this->rdvNotificationService->notifyMedecinOnPatientCancellation($rdv);
        $this->addFlash('success', 'Rendez-vous annulÃ©.');
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

    #[Route('/notifications', name: 'app_patient_notifications', methods: ['GET'])]
    public function notifications(): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $notifications = $this->notificationRepo->findBy(
            ['utilisateur' => $patient],
            ['dateCreation' => 'DESC']
        );
        $unreadCount = $this->notificationRepo->countUnreadByUser($patient);

        return $this->render('patient/notifications.html.twig', [
            'patient' => $patient,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/notifications/tout-lire', name: 'app_patient_notifications_tout_lire', methods: ['POST'])]
    public function marquerToutesNotificationsLues(Request $request): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return $this->redirectToRoute('app_profile');
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('notifications_mark_all', $token)) {
            $this->addFlash('error', 'Jeton de securite invalide.');
            return $this->redirectToRoute('app_patient_notifications');
        }

        $updated = $this->notificationRepo->markAllAsReadForUser($patient);
        $this->addFlash('success', $updated > 0 ? 'Toutes les notifications ont ete marquees comme lues.' : 'Aucune notification non lue.');

        return $this->redirectToRoute('app_patient_notifications');
    }

    #[Route('/notification/{id}/lu', name: 'app_patient_notification_lue', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function marquerNotificationLue(Request $request, Notification $notification): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorise.'], 403);
        }

        if ($notification->getUtilisateur() !== $patient) {
            return new JsonResponse(['success' => false, 'message' => 'Notification introuvable.'], 404);
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('notification_read_' . $notification->getId(), $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Jeton de securite invalide.'], 400);
        }

        if (!$notification->isEstLu()) {
            $notification->setEstLu(true);
            $this->em->flush();
        }

        return new JsonResponse([
            'success' => true,
            'unreadCount' => $this->notificationRepo->countUnreadByUser($patient),
        ]);
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
            $this->addFlash('error', 'Document non trouvÃ©.');
            return $this->redirectToRoute('app_patient_dossier');
        }

        if (!$document->isAjouteParPatient()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que les documents que vous avez ajoutÃ©s.');
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
        $this->addFlash('success', 'Document supprimÃ©.');
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
            $this->addFlash('error', 'Document non trouvÃ©.');
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
            return new JsonResponse(['success' => false, 'message' => 'Non autorisÃ©.'], 403);
        }

        $token = $request->request->get('_token');
        if (!$token || !$this->isCsrfTokenValid('rdv_ajax', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token invalide.'], 400);
        }

        $medecinId = $request->request->get('medecin');
        $dateDebut = $request->request->get('dateDebut');
        $noteRaw = $request->request->get('note', '');

        if ($medecinId === null || $medecinId === '' || !is_numeric($medecinId)) {
            return new JsonResponse(['success' => false, 'message' => 'MÃ©decin requis.'], 400);
        }
        $medecinId = (int) $medecinId;
        if ($medecinId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'MÃ©decin invalide.'], 400);
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
            return new JsonResponse(['success' => false, 'message' => 'La note ne doit pas dÃ©passer 2000 caractÃ¨res.'], 400);
        }
        $note = $note !== '' ? $note : null;

        $medecin = $this->medecinRepo->find($medecinId);
        if (!$medecin || $medecin->getStatut() !== \App\Entity\StatutCompte::ACTIF) {
            return new JsonResponse(['success' => false, 'message' => 'MÃ©decin invalide.'], 400);
        }

        try {
            $date = new \DateTime($dateDebut);
            if ($date <= new \DateTime()) {
                return new JsonResponse(['success' => false, 'message' => 'La date doit Ãªtre dans le futur.'], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Date invalide.'], 400);
        }

        $validation = $this->rdvBookingValidator->validateRequestedSlot($medecin, $date);
        if ($validation['ok'] !== true) {
            return new JsonResponse(['success' => false, 'message' => $validation['message']], 400);
        }

        $endAt = $validation['endAt'] instanceof \DateTimeInterface
            ? \DateTime::createFromInterface($validation['endAt'])
            : (clone $date)->modify('+30 minutes');

        $rdv = new RendezVous();
        $rdv->setPatient($patient);
        $rdv->setMedecin($medecin);
        $rdv->setDateDebut($date);
        $rdv->setDateFin($endAt);
        $rdv->setNote($note);
        $medecin->addRendezVous($rdv);
        $this->em->persist($rdv);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Demande de rendez-vous envoyÃ©e. La secrÃ©taire du mÃ©decin la validera sous peu.',
        ]);
    }

    //  CHATBOT API 

    #[Route('/chatbot/medecins', name: 'app_patient_chatbot_medecins', methods: ['GET'])]
    public function chatbotMedecins(Request $request): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorisÃ©.'], 403);
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
            'specialite' => $m->getSpecialite() ?? 'MÃ©decin gÃ©nÃ©raliste',
        ], $medecins);

        $specialites = $this->medecinRepo->findSpecialitesDistinctes();

        return new JsonResponse([
            'success'     => true,
            'medecins'    => $data,
            'specialites' => $specialites,
        ]);
    }

    #[Route('/chatbot/interpret', name: 'app_patient_chatbot_interpret', methods: ['POST'])]
    public function chatbotInterpret(Request $request): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorise.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->request->all();
        }

        $message = isset($data['message']) && is_string($data['message']) ? trim($data['message']) : '';
        $state = isset($data['state']) && is_array($data['state']) ? $data['state'] : [];
        $step = isset($state['step']) && is_string($state['step']) ? trim($state['step']) : 'UNKNOWN';

        if ($message === '') {
            return new JsonResponse(['success' => false, 'message' => 'Message vide.'], 400);
        }

        $allMedecins = $this->medecinRepo->findMedecinsActifsAvecFiltres(null, null, 'az');
        $allMedecinsData = $this->mapChatbotMedecins($allMedecins);
        $specialites = $this->medecinRepo->findSpecialitesDistinctes();

        $ai = $this->chatbotAiService->interpretMessage($message, $step, [
            'state' => $state,
            'specialites' => $specialites,
            'medecins' => $allMedecinsData,
        ]);

        $response = [
            'success' => true,
            'aiEnabled' => $this->chatbotAiService->isEnabled(),
            'action' => $ai['action'],
            'reply' => $ai['reply'] ?? null,
            'nextStep' => $step,
            'date' => $ai['date'] ?? null,
            'time' => $ai['time'] ?? null,
            'selectedDoctor' => null,
            'doctors' => [],
            'showConfirm' => false,
            'rdvSubmitted' => false,
        ];

        $action = strtoupper((string) ($ai['action'] ?? 'UNKNOWN'));
        switch ($action) {
            case 'OUT_OF_SCOPE':
                $response['nextStep'] = 'INTENT';
                $response['reply'] ??= 'Ce n\'est pas mon role. Je peux seulement vous aider a trouver un medecin et prendre un rendez-vous.';
                break;

            case 'LIST_DOCTORS':
                $response['doctors'] = $allMedecinsData;
                $response['nextStep'] = 'RDV_DOCTOR';
                $response['reply'] ??= 'Voici la liste des medecins disponibles.';
                break;

            case 'EXPLORE':
            case 'PICK_DOCTOR':
                $doctorQuery = isset($ai['doctorQuery']) && is_string($ai['doctorQuery']) ? trim($ai['doctorQuery']) : '';
                $specialityQuery = isset($ai['specialityQuery']) && is_string($ai['specialityQuery']) ? trim($ai['specialityQuery']) : '';

                $found = $this->medecinRepo->findMedecinsActifsAvecFiltres(
                    $doctorQuery !== '' ? $doctorQuery : null,
                    $specialityQuery !== '' ? $specialityQuery : null,
                    'az'
                );
                $foundData = $this->mapChatbotMedecins($found);

                if (count($foundData) === 1) {
                    $response['selectedDoctor'] = $foundData[0];
                    $response['nextStep'] = 'RDV_DATE';
                    $response['reply'] ??= 'Medecin trouve. Donnez maintenant la date souhaitee.';
                } else {
                    $response['doctors'] = $foundData;
                    $response['nextStep'] = 'RDV_DOCTOR';
                    $response['reply'] ??= count($foundData) > 0
                        ? 'Voici les medecins trouves.'
                        : 'Aucun medecin trouve. Essayez avec un autre nom ou une specialite.';
                }
                break;

            case 'BOOK':
                $response['nextStep'] = 'RDV_DOCTOR';
                $response['reply'] ??= 'Parfait. Donnez le nom du medecin ou la specialite.';
                break;

            case 'SET_DATE':
            case 'CHANGE_DATE':
                if (!is_string($response['date']) || $response['date'] === '') {
                    $response['nextStep'] = 'RDV_DATE';
                    $response['reply'] ??= 'Je n\'ai pas compris la date. Essayez le format 25/03/2026.';
                } else {
                    $response['nextStep'] = 'RDV_TIME';
                    $response['reply'] ??= 'Date bien prise en compte. Donnez maintenant l\'heure.';
                }
                break;

            case 'SET_TIME':
            case 'CHANGE_TIME':
                if (
                    (!is_string($response['time']) || $response['time'] === '')
                    && is_string($response['date'])
                    && $response['date'] !== ''
                ) {
                    $response['nextStep'] = 'RDV_TIME';
                    $response['reply'] ??= 'Date bien prise en compte. Donnez maintenant l\'heure.';
                    break;
                }

                if (!is_string($response['time']) || $response['time'] === '') {
                    $response['nextStep'] = 'RDV_TIME';
                    $response['reply'] ??= 'Je n\'ai pas compris l\'heure. Essayez 14h30 ou 14:30.';
                } else {
                    $response['nextStep'] = 'CONFIRM';
                    $response['showConfirm'] = true;
                    $response['reply'] ??= 'Heure enregistree. Verifiez le recapitulatif puis confirmez.';
                }
                break;

            case 'CONFIRM':
                $stateMedecinId = isset($state['medecin']['id']) ? (int) $state['medecin']['id'] : 0;
                $stateDate = isset($state['date']) && is_string($state['date']) ? trim($state['date']) : '';
                $stateHeure = isset($state['heure']) && is_string($state['heure']) ? trim($state['heure']) : '';

                if ($stateMedecinId <= 0 || $stateDate === '' || $stateHeure === '') {
                    $response['nextStep'] = 'RDV_DOCTOR';
                    $response['reply'] ??= 'Il manque des informations (medecin, date, heure) avant la confirmation.';
                    break;
                }

                $medecin = $this->medecinRepo->find($stateMedecinId);
                if (!$medecin || $medecin->getStatut() !== \App\Entity\StatutCompte::ACTIF) {
                    $response['nextStep'] = 'RDV_DOCTOR';
                    $response['reply'] = 'Medecin introuvable. Veuillez en choisir un autre.';
                    break;
                }

                try {
                    $dateDebut = new \DateTime($stateDate . ' ' . $stateHeure);
                } catch (\Exception) {
                    $response['nextStep'] = 'RDV_DATE';
                    $response['reply'] = 'Date ou heure invalide. Veuillez corriger.';
                    break;
                }

                if ($dateDebut <= new \DateTime()) {
                    $response['nextStep'] = 'RDV_DATE';
                    $response['reply'] = 'La date doit etre dans le futur.';
                    break;
                }

                $validation = $this->rdvBookingValidator->validateRequestedSlot($medecin, $dateDebut);
                if ($validation['ok'] !== true) {
                    $response['nextStep'] = 'RDV_TIME';
                    $response['reply'] = $validation['message'];
                    break;
                }

                $endAt = $validation['endAt'] instanceof \DateTimeInterface
                    ? \DateTime::createFromInterface($validation['endAt'])
                    : (clone $dateDebut)->modify('+30 minutes');

                $rdv = new RendezVous();
                $rdv->setPatient($patient);
                $rdv->setMedecin($medecin);
                $rdv->setDateDebut($dateDebut);
                $rdv->setDateFin($endAt);
                $rdv->setNote('Demande via chatbot OpenAI');
                $medecin->addRendezVous($rdv);
                $this->em->persist($rdv);
                $this->em->flush();

                $response['nextStep'] = 'DONE';
                $response['rdvSubmitted'] = true;
                $response['reply'] = 'Votre demande de rendez-vous a ete envoyee. La secretaire vous confirmera bientot.';
                break;

            default:
                $response['nextStep'] = $step;
                $response['reply'] ??= 'Je peux vous aider a trouver un medecin ou prendre un rendez-vous.';
                break;
        }

        return new JsonResponse($response);
    }

    #[Route('/chatbot/rdv', name: 'app_patient_chatbot_rdv', methods: ['POST'])]
    public function chatbotRdv(Request $request): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorisÃ©.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->request->all();
        }

        $medecinId = isset($data['medecinId']) ? (int) $data['medecinId'] : 0;
        $dateDebut = isset($data['dateDebut']) && is_string($data['dateDebut']) ? trim($data['dateDebut']) : '';

        if ($medecinId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'MÃ©decin requis.'], 400);
        }
        if ($dateDebut === '') {
            return new JsonResponse(['success' => false, 'message' => 'Date et heure requises.'], 400);
        }

        $medecin = $this->medecinRepo->find($medecinId);
        if (!$medecin || $medecin->getStatut() !== \App\Entity\StatutCompte::ACTIF) {
            return new JsonResponse(['success' => false, 'message' => 'MÃ©decin introuvable.'], 400);
        }

        try {
            $date = new \DateTime($dateDebut);
            if ($date <= new \DateTime()) {
                return new JsonResponse(['success' => false, 'message' => 'La date doit Ãªtre dans le futur.'], 400);
            }
        } catch (\Exception) {
            return new JsonResponse(['success' => false, 'message' => 'Date invalide.'], 400);
        }

        $validation = $this->rdvBookingValidator->validateRequestedSlot($medecin, $date);
        if ($validation['ok'] !== true) {
            return new JsonResponse(['success' => false, 'message' => $validation['message']], 400);
        }

        $endAt = $validation['endAt'] instanceof \DateTimeInterface
            ? \DateTime::createFromInterface($validation['endAt'])
            : (clone $date)->modify('+30 minutes');

        $rdv = new RendezVous();
        $rdv->setPatient($patient);
        $rdv->setMedecin($medecin);
        $rdv->setDateDebut($date);
        $rdv->setDateFin($endAt);
        $rdv->setNote('Demande via chatbot');
        $medecin->addRendezVous($rdv);
        $this->em->persist($rdv);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre demande de rendez-vous a bien Ã©tÃ© envoyÃ©e ! La secrÃ©taire du Dr ' . $medecin->getNomComplet() . ' la validera sous peu.',
        ]);
    }
    /**
     * @param iterable<\App\Entity\Medecin> $medecins
     *
     * @return array<int,array{id:int,nom:string,specialite:string}>
     */
    private function mapChatbotMedecins(iterable $medecins): array
    {
        $data = [];
        foreach ($medecins as $m) {
            $data[] = [
                'id' => $m->getId(),
                'nom' => $m->getNomComplet(),
                'specialite' => $m->getSpecialite() ?? 'Medecin generaliste',
            ];
        }

        return $data;
    }
}

