<?php

namespace App\Controller;

use App\Entity\CategorieSante;
use App\Entity\ProgressionUtilisateur;
use App\Entity\CoursEducatif;
use App\Entity\Medecin;
use App\Entity\Admin;
use App\Entity\Utilisateur;
use App\Entity\ReponseUtilisateur;
use App\Entity\QuestionQuiz;
use App\Enum\StatutQuestion;
use App\Form\CategorieSanteFormType;
use App\Repository\CategorieSanteRepository;
use App\Repository\ProgressionUtilisateurRepository;
use App\Repository\CoursEducatifRepository;
use App\Repository\ReponseUtilisateurRepository;
use App\SavoirMedicalBundle\Service\GroqAIService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/savoir-medical')]
#[IsGranted('ROLE_USER')]
class SavoirMedicalController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategorieSanteRepository $categorieSanteRepository,
        private ProgressionUtilisateurRepository $progressionRepository,
        private CoursEducatifRepository $coursRepository,
        private ReponseUtilisateurRepository $reponseRepository,
        private NotificationService $notificationService
    ) {
    }

    /**
     * Check if user is a médecin or admin
     */
    private function isMedecinOrAdmin(): bool
    {
        return $this->isGranted('ROLE_MEDECIN') || $this->isGranted('ROLE_ADMIN');
    }

    #[Route('/', name: 'app_savoir_medical_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Filter categories based on user role
        if ($this->isGranted('ROLE_ADMIN')) {
            // Admin sees all categories
            $categories = $this->categorieSanteRepository->findAll();
        } elseif ($this->isGranted('ROLE_MEDECIN')) {
            // Médecin sees approved categories + their own pending ones
            $categories = $this->categorieSanteRepository->createQueryBuilder('c')
                ->where('c.statut = :approuve')
                ->orWhere('c.creePar = :medecin')
                ->setParameter('approuve', \App\Enum\StatutCategorie::APPROUVE)
                ->setParameter('medecin', $user)
                ->getQuery()
                ->getResult();
        } else {
            // Patients see only approved categories
            $categories = $this->categorieSanteRepository->findBy(['statut' => \App\Enum\StatutCategorie::APPROUVE]);
        }
        
        // Récupérer les progressions de l'utilisateur (uniquement pour les patients)
        $progressionsParCategorie = [];
        if ($this->isGranted('ROLE_PATIENT')) {
            $progressions = $this->progressionRepository->findByUtilisateur($user);
            foreach ($progressions as $progression) {
                $progressionsParCategorie[$progression->getCategorieSante()->getId()->toRfc4122()] = $progression;
            }
        }
        
        return $this->render('savoir_medical/index.html.twig', [
            'categories' => $categories,
            'progressionsParCategorie' => $progressionsParCategorie,
        ]);
    }

    #[Route('/categorie/nouvelle', name: 'app_savoir_medical_nouvelle_categorie')]
    public function nouvelleCategorie(Request $request): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour créer une catégorie');
        }

        $categorie = new CategorieSante();
        $form = $this->createForm(CategorieSanteFormType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set creator if médecin
            $user = $this->getUser();
            if ($user instanceof \App\Entity\Medecin) {
                $categorie->setCreePar($user);
            }
            
            // Admin can directly approve, médecin needs approval
            if ($user instanceof \App\Entity\Admin) {
                $categorie->setStatut(\App\Enum\StatutCategorie::APPROUVE);
                $categorie->setApprouvePar($user);
                $categorie->setDateApprobation(new \DateTime());
            } else {
                $categorie->setStatut(\App\Enum\StatutCategorie::EN_ATTENTE);
            }
            
            $this->entityManager->persist($categorie);
            $this->entityManager->flush();

            // Send notifications
            if ($user instanceof \App\Entity\Admin) {
                // Admin created and approved - notify all patients
                $this->notificationService->notifierPatientsNouvelleCategorie($categorie);
                $this->addFlash('success', 'La catégorie a été créée et approuvée avec succès !');
            } else {
                // Médecin created - notify admins for approval
                $this->notificationService->notifierAdminNouvelleCategorie($categorie, $user);
                $this->addFlash('success', 'La catégorie a été créée et est en attente d\'approbation par un administrateur.');
            }
            
            return $this->redirectToRoute('app_savoir_medical_index');
        }

        return $this->render('savoir_medical/form_categorie.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie,
            'isEdit' => false,
        ]);
    }

    #[Route('/categorie/{id}', name: 'app_savoir_medical_categorie')]
    public function categorie(CategorieSante $categorie): Response
    {
        $user = $this->getUser();
        
        // Récupérer les cours de cette catégorie
        $cours = $this->coursRepository->findByCategorie($categorie);
        
        // Récupérer la progression de l'utilisateur pour cette catégorie (uniquement pour les patients)
        $progression = null;
        if ($this->isGranted('ROLE_PATIENT')) {
            $progression = $this->progressionRepository->findByUtilisateurAndCategorie($user, $categorie);
        }
        
        return $this->render('savoir_medical/categorie.html.twig', [
            'categorie' => $categorie,
            'cours' => $cours,
            'progression' => $progression,
        ]);
    }

    #[Route('/progression', name: 'app_progression_utilisateur')]
    #[IsGranted('ROLE_PATIENT')]
    public function progression(): Response
    {
        $user = $this->getUser();
        
        // Récupérer toutes les progressions de l'utilisateur
        $progressions = $this->progressionRepository->findByUtilisateur($user);
        
        // Statistiques
        $totalCategories = $this->categorieSanteRepository->count([]);
        $categoriesCompletes = $this->progressionRepository->count([
            'utilisateur' => $user,
            'estComplete' => true
        ]);
        
        $totalScoreMax = 0;
        $totalTentatives = 0;
        $badges = [];
        
        foreach ($progressions as $progression) {
            $totalScoreMax += $progression->getScoreMax();
            $totalTentatives += $progression->getNbTentatives();
            if ($progression->getBadgeNom() && $progression->getCategorieSante()) {
                $badges[] = [
                    'nom' => $progression->getBadgeNom(),
                    'categorie' => $progression->getCategorieSante()->getNom(),
                    'date' => $progression->getDateObtention(),
                ];
            }
        }
        
        $tauxCompletion = $totalCategories > 0 ? round(($categoriesCompletes / $totalCategories) * 100) : 0;
        
        return $this->render('savoir_medical/progression.html.twig', [
            'progressions' => $progressions,
            'stats' => [
                'totalCategories' => $totalCategories,
                'categoriesCompletes' => $categoriesCompletes,
                'tauxCompletion' => $tauxCompletion,
                'totalScoreMax' => $totalScoreMax,
                'totalTentatives' => $totalTentatives,
            ],
            'badges' => $badges,
        ]);
    }

    #[Route('/cours/{id}', name: 'app_savoir_medical_cours')]
    public function cours(CoursEducatif $cours): Response
    {
        $user = $this->getUser();
        
        // Récupérer la progression pour cette catégorie (uniquement pour les patients)
        $progression = null;
        if ($this->isGranted('ROLE_PATIENT')) {
            $progression = $this->progressionRepository->findByUtilisateurAndCategorie(
                $user,
                $cours->getCategorieSante()
            );
        }
        
        // Récupérer les questions du cours
        $questions = $cours->getQuestions();
        
        return $this->render('savoir_medical/cours.html.twig', [
            'cours' => $cours,
            'progression' => $progression,
            'questions' => $questions,
        ]);
    }

    #[Route('/categorie/{id}/modifier', name: 'app_savoir_medical_modifier_categorie')]
    public function modifierCategorie(Request $request, CategorieSante $categorie): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour modifier une catégorie');
        }

        $form = $this->createForm(CategorieSanteFormType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'La catégorie a été modifiée avec succès !');
            return $this->redirectToRoute('app_savoir_medical_categorie', ['id' => $categorie->getId()]);
        }

        return $this->render('savoir_medical/form_categorie.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie,
            'isEdit' => true,
        ]);
    }

    #[Route('/categorie/{id}/supprimer', name: 'app_savoir_medical_supprimer_categorie', methods: ['POST'])]
    public function supprimerCategorie(Request $request, CategorieSante $categorie): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour supprimer une catégorie');
        }

        if ($this->isCsrfTokenValid('delete'.$categorie->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($categorie);
            $this->entityManager->flush();

            $this->addFlash('success', 'La catégorie a été supprimée avec succès !');
        }

        return $this->redirectToRoute('app_savoir_medical_index');
    }

    #[Route('/categorie/{categorieId}/cours/nouveau', name: 'app_savoir_medical_nouveau_cours')]
    public function nouveauCours(Request $request, string $categorieId): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour créer un cours');
        }

        $categorie = $this->categorieSanteRepository->find($categorieId);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }
        
        $cours = new CoursEducatif();
        $cours->setCategorieSante($categorie);
        
        // Set the current médecin or admin as validator
        $user = $this->getUser();
        if ($user instanceof Medecin) {
            $cours->setMedecinValidateur($user);
        } elseif ($user instanceof Admin) {
            // For admin, we can set a reference or leave it null
            // depending on your business logic
            $cours->setMedecinValidateur(null);
        }
        
        if ($request->isMethod('POST')) {
            $cours->setTitre($request->request->get('titre'));
            $cours->setContenu($request->request->get('contenu'));
            $cours->setScorePourBadge((int) $request->request->get('scorePourBadge', 100));
            
            $this->entityManager->persist($cours);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le cours a été créé avec succès !');
            return $this->redirectToRoute('app_savoir_medical_categorie', ['id' => $categorieId]);
        }
        
        return $this->render('savoir_medical/form_cours.html.twig', [
            'cours' => $cours,
            'categorie' => $categorie,
            'isEdit' => false,
        ]);
    }
    
    #[Route('/cours/{id}/modifier', name: 'app_savoir_medical_modifier_cours')]
    public function modifierCours(Request $request, CoursEducatif $cours): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour modifier un cours');
        }

        if ($request->isMethod('POST')) {
            $cours->setTitre($request->request->get('titre'));
            $cours->setContenu($request->request->get('contenu'));
            $cours->setScorePourBadge((int) $request->request->get('scorePourBadge', 100));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le cours a été modifié avec succès !');
            return $this->redirectToRoute('app_savoir_medical_categorie', ['id' => $cours->getCategorieSante()->getId()]);
        }
        
        return $this->render('savoir_medical/form_cours.html.twig', [
            'cours' => $cours,
            'categorie' => $cours->getCategorieSante(),
            'isEdit' => true,
        ]);
    }
    
    #[Route('/cours/{id}/supprimer', name: 'app_savoir_medical_supprimer_cours', methods: ['POST'])]
    public function supprimerCours(Request $request, CoursEducatif $cours): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour supprimer un cours');
        }

        $categorieId = $cours->getCategorieSante()->getId();
        
        if ($this->isCsrfTokenValid('delete'.$cours->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($cours);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le cours a été supprimé avec succès !');
        }
        
        return $this->redirectToRoute('app_savoir_medical_categorie', ['id' => $categorieId]);
    }

    #[Route('/cours/{coursId}/quiz/gerer', name: 'app_quiz_gerer')]
    public function gererQuiz(string $coursId): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour gérer les quiz');
        }

        $cours = $this->coursRepository->find($coursId);
        
        if (!$cours) {
            throw $this->createNotFoundException('Cours non trouvé');
        }
        
        // Get all questions for this course
        $questions = $cours->getQuestions();
        
        return $this->render('savoir_medical/quiz_gerer.html.twig', [
            'cours' => $cours,
            'questions' => $questions,
        ]);
    }
    
    #[Route('/cours/{coursId}/quiz/generer-ai', name: 'app_quiz_generer_ai', methods: ['POST'])]
    public function genererQuizIA(Request $request, string $coursId, GroqAIService $groqAIService): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour générer des quiz');
        }

        $cours = $this->coursRepository->find($coursId);
        
        if (!$cours) {
            throw $this->createNotFoundException('Cours non trouvé');
        }
        
        // Get number of questions from form
        $nombreQuestions = (int) $request->request->get('nombreQuestions', 1);
        $nombreQuestions = max(1, min($nombreQuestions, 10)); // Between 1 and 10
        
        $questionsGenerees = 0;
        $erreurs = [];
        
        try {
            error_log('Starting AI generation for course: ' . $coursId);
            error_log('Number of questions to generate: ' . $nombreQuestions);
            error_log('Course content length: ' . strlen($cours->getContenu()));
            error_log('Category name: ' . $cours->getCategorieSante()->getNom());
            
            // Generate multiple questions
            for ($i = 0; $i < $nombreQuestions; $i++) {
                try {
                    $questionData = $groqAIService->generateQuizQuestion(
                        $cours->getContenu(),
                        $cours->getCategorieSante()->getNom(),
                        $i + 1 // Pass question number for variety
                    );
                    
                    // Validate question data
                    if (empty($questionData['question'])) {
                        throw new \RuntimeException('La question générée est vide');
                    }
                    
                    // Create Question entity
                    $question = new QuestionQuiz();
                    $question->setCoursEducatif($cours);
                    $question->setEnonce($questionData['question']);
                    $question->setOptionsReponsesArray($questionData['options']);
                    $question->setReponseCorrecte($questionData['correct_answer']);
                    $question->setExplication($questionData['explanation'] ?? '');
                    $question->setStatut(StatutQuestion::IA_PROPOSE);
                    
                    // Save to database
                    $this->entityManager->persist($question);
                    $questionsGenerees++;
                    
                    error_log('Question ' . ($i + 1) . ' generated successfully');
                    
                    // Small delay to avoid rate limiting (0.5 seconds)
                    if ($i < $nombreQuestions - 1) {
                        usleep(500000);
                    }
                    
                } catch (\Exception $e) {
                    error_log('Error generating question ' . ($i + 1) . ': ' . $e->getMessage());
                    $erreurs[] = 'Question ' . ($i + 1) . ': ' . $e->getMessage();
                    continue;
                }
            }
            
            // Flush all questions at once
            $this->entityManager->flush();
            
            // Show success message
            if ($questionsGenerees > 0) {
                $message = $questionsGenerees . ' question(s) générée(s) avec succès par l\'IA ! Vous pouvez les réviser et les valider.';
                if (!empty($erreurs)) {
                    $message .= ' (' . count($erreurs) . ' erreur(s) rencontrée(s))';
                }
                $this->addFlash('success', $message);
            } else {
                $this->addFlash('error', 'Aucune question n\'a pu être générée. Erreurs: ' . implode(', ', $erreurs));
            }
            
        } catch (\Exception $e) {
            error_log('Error generating questions: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->addFlash('error', 'Erreur lors de la génération: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_quiz_gerer', ['coursId' => $coursId]);
    }
    
    #[Route('/question/{questionId}/supprimer', name: 'app_quiz_supprimer_question', methods: ['POST'])]
    public function supprimerQuestion(Request $request, string $questionId): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour supprimer une question');
        }

        try {
            $uuid = Uuid::fromString($questionId);
            $question = $this->entityManager->getRepository(QuestionQuiz::class)->find($uuid);
        } catch (\Exception $e) {
            $question = null;
        }
        
        if (!$question) {
            throw $this->createNotFoundException('Question non trouvée');
        }
        
        $coursId = $question->getCoursEducatif()->getId();
        
        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($question);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Question supprimée avec succès !');
        }
        
        return $this->redirectToRoute('app_quiz_gerer', ['coursId' => $coursId]);
    }
    
    #[Route('/question/{questionId}/valider', name: 'app_quiz_valider_question', methods: ['POST'])]
    public function validerQuestion(Request $request, string $questionId): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour valider une question');
        }

        try {
            $uuid = Uuid::fromString($questionId);
            $question = $this->entityManager->getRepository(QuestionQuiz::class)->find($uuid);
        } catch (\Exception $e) {
            $question = null;
        }
        
        if (!$question) {
            throw $this->createNotFoundException('Question non trouvée');
        }
        
        $coursId = $question->getCoursEducatif()->getId();
        
        if ($this->isCsrfTokenValid('validate'.$question->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if ($user instanceof Medecin) {
                $question->validerParMedecin($user);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Question validée avec succès ! Elle est maintenant visible pour les patients.');
            } elseif ($user instanceof Admin) {
                // For admin, we can set a validation flag or handle it differently
                // For now, we'll mark it as validated without a specific medecin
                $question->setStatut(StatutQuestion::VALIDE_MEDECIN);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Question validée avec succès ! Elle est maintenant visible pour les patients.');
            }
        }
        
        return $this->redirectToRoute('app_quiz_gerer', ['coursId' => $coursId]);
    }
    
    #[Route('/question/{questionId}/modifier', name: 'app_quiz_modifier_question')]
    public function modifierQuestion(Request $request, string $questionId): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour modifier une question');
        }

        try {
            $uuid = Uuid::fromString($questionId);
            $question = $this->entityManager->getRepository(QuestionQuiz::class)->find($uuid);
        } catch (\Exception $e) {
            $question = null;
        }
        
        if (!$question) {
            throw $this->createNotFoundException('Question non trouvée');
        }
        
        $cours = $question->getCoursEducatif();
        
        if ($request->isMethod('POST')) {
            $question->setEnonce($request->request->get('enonce'));
            
            // Parse options
            $options = [];
            for ($i = 1; $i <= 4; $i++) {
                $option = $request->request->get('option' . $i);
                if ($option) {
                    $options[] = $option;
                }
            }
            $question->setOptionsReponsesArray($options);
            
            $question->setReponseCorrecte($request->request->get('reponseCorrecte'));
            $question->setExplication($request->request->get('explication'));
            
            // If modifying an AI-proposed question, mark it as validated
            if ($question->getStatut() === StatutQuestion::IA_PROPOSE) {
                $user = $this->getUser();
                if ($user instanceof Medecin) {
                    $question->validerParMedecin($user);
                } elseif ($user instanceof Admin) {
                    $question->setStatut(StatutQuestion::VALIDE_MEDECIN);
                }
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Question modifiée avec succès !');
            return $this->redirectToRoute('app_quiz_gerer', ['coursId' => $cours->getId()]);
        }
        
        return $this->render('savoir_medical/quiz_modifier.html.twig', [
            'cours' => $cours,
            'question' => $question,
        ]);
    }
    
    #[Route('/cours/{coursId}/quiz/preview', name: 'app_quiz_preview')]
    public function previewQuiz(string $coursId): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour prévisualiser un quiz');
        }

        $cours = $this->coursRepository->find($coursId);
        
        if (!$cours) {
            throw $this->createNotFoundException('Cours non trouvé');
        }
        
        // Get only validated questions (what patients will see)
        $questionsValidees = $this->entityManager->getRepository(QuestionQuiz::class)
            ->createQueryBuilder('q')
            ->where('q.coursEducatif = :cours')
            ->andWhere('q.statut = :statut')
            ->setParameter('cours', $cours)
            ->setParameter('statut', StatutQuestion::VALIDE_MEDECIN)
            ->getQuery()
            ->getResult();
        
        return $this->render('savoir_medical/quiz_preview.html.twig', [
            'cours' => $cours,
            'questionsValidees' => $questionsValidees,
        ]);
    }
    
    #[Route('/cours/{coursId}/quiz/prendre', name: 'app_quiz_prendre')]
    #[IsGranted('ROLE_PATIENT')]
    public function prendreQuiz(string $coursId): Response
    {
        $cours = $this->coursRepository->find($coursId);
        
        if (!$cours) {
            throw $this->createNotFoundException('Cours non trouvé');
        }
        
        // Get only VALIDATED questions using query builder for better reliability
        $questions = $this->entityManager->getRepository(QuestionQuiz::class)
            ->createQueryBuilder('q')
            ->where('q.coursEducatif = :cours')
            ->andWhere('q.statut = :statut')
            ->setParameter('cours', $cours)
            ->setParameter('statut', StatutQuestion::VALIDE_MEDECIN)
            ->getQuery()
            ->getResult();
        
        return $this->render('savoir_medical/quiz_prendre.html.twig', [
            'cours' => $cours,
            'questions' => $questions,
        ]);
    }

    #[Route('/cours/{coursId}/quiz/soumettre', name: 'app_quiz_soumettre', methods: ['POST'])]
    #[IsGranted('ROLE_PATIENT')]
    public function soumettreQuiz(Request $request, string $coursId): Response
    {
        $cours = $this->coursRepository->find($coursId);

        if (!$cours) {
            throw $this->createNotFoundException('Cours non trouvé');
        }

        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour soumettre un quiz');
        }

        // Get submitted answers
        $reponsesJson = $request->request->get('reponses');
        $reponses = json_decode($reponsesJson, true);

        $totalScore = 0;
        $correctAnswers = 0;
        $totalQuestions = count($reponses);

        // Save each answer
        foreach ($reponses as $questionId => $answerData) {
            $question = $this->entityManager->getRepository(QuestionQuiz::class)->find($questionId);

            if ($question) {
                $reponseUtilisateur = new ReponseUtilisateur();
                $reponseUtilisateur->setUtilisateur($user);
                $reponseUtilisateur->setQuestion($question);
                $reponseUtilisateur->setReponseChoisie($answerData['selected']);
                $reponseUtilisateur->setEstCorrecte($answerData['correct']);
                $reponseUtilisateur->setPointsObtenus($answerData['points']);

                $this->entityManager->persist($reponseUtilisateur);

                $totalScore += $answerData['points'];
                if ($answerData['correct']) {
                    $correctAnswers++;
                }
            }
        }

        // Update or create progression
        $progression = $this->entityManager->getRepository(ProgressionUtilisateur::class)
            ->findOneBy([
                'utilisateur' => $user,
                'categorieSante' => $cours->getCategorieSante()
            ]);

        if (!$progression) {
            $progression = new ProgressionUtilisateur();
            $progression->setUtilisateur($user);
            $progression->setCategorieSante($cours->getCategorieSante());
        }

        // Update progression
        $progression->incrementNbTentatives();

        // Update max score if current score is higher
        if ($totalScore > $progression->getScoreMax()) {
            $progression->setScoreMax($totalScore);
        }

        // Award badges based on score percentage
        $scorePercentage = ($correctAnswers / $totalQuestions) * 100;

        if ($scorePercentage >= 90 && !$progression->getBadgeNom()) {
            $progression->setBadgeNom('Expert');
            $progression->setDateObtention(new \DateTime());
            $this->addFlash('success', '🏆 Félicitations ! Vous avez obtenu le badge Expert !');
        } elseif ($scorePercentage >= 70 && !$progression->getBadgeNom()) {
            $progression->setBadgeNom('Avancé');
            $progression->setDateObtention(new \DateTime());
            $this->addFlash('success', '🎖️ Félicitations ! Vous avez obtenu le badge Avancé !');
        } elseif ($scorePercentage >= 50 && !$progression->getBadgeNom()) {
            $progression->setBadgeNom('Intermédiaire');
            $progression->setDateObtention(new \DateTime());
            $this->addFlash('success', '🥉 Félicitations ! Vous avez obtenu le badge Intermédiaire !');
        }

        // Mark as complete if score is high enough
        if ($scorePercentage >= 70) {
            $progression->setEstComplete(true);
        }

        $this->entityManager->persist($progression);
        $this->entityManager->flush();

        // Redirect to results page with score
        return $this->render('savoir_medical/quiz_resultat.html.twig', [
            'cours' => $cours,
            'totalScore' => $totalScore,
            'correctAnswers' => $correctAnswers,
            'totalQuestions' => $totalQuestions,
            'scorePercentage' => $scorePercentage,
            'progression' => $progression,
        ]);
    }

    
    #[Route('/cours/{coursId}/quiz/ajouter', name: 'app_quiz_ajouter_manuel')]
    public function ajouterQuestionManuel(Request $request, string $coursId): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour ajouter une question');
        }

        $cours = $this->coursRepository->find($coursId);
        
        if (!$cours) {
            throw $this->createNotFoundException('Cours non trouvé');
        }
        
        if ($request->isMethod('POST')) {
            $question = new QuestionQuiz();
            $question->setCoursEducatif($cours);
            $question->setEnonce($request->request->get('enonce'));
            
            // Parse options
            $options = [];
            for ($i = 1; $i <= 4; $i++) {
                $option = $request->request->get('option' . $i);
                if ($option) {
                    $options[] = $option;
                }
            }
            $question->setOptionsReponsesArray($options);
            
            $question->setReponseCorrecte($request->request->get('reponseCorrecte'));
            $question->setExplication($request->request->get('explication'));
            $question->setStatut(StatutQuestion::VALIDE_MEDECIN);
            
            $user = $this->getUser();
            if ($user instanceof Medecin) {
                $question->setMedecinValidateur($user);
            } elseif ($user instanceof Admin) {
                // For admin, we can set it to null or handle it differently
                $question->setMedecinValidateur(null);
            }
            
            $this->entityManager->persist($question);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Question ajoutée avec succès !');
            return $this->redirectToRoute('app_quiz_gerer', ['coursId' => $coursId]);
        }
        
        return $this->render('savoir_medical/quiz_form.html.twig', [
            'cours' => $cours,
            'question' => null,
        ]);
    }

    #[Route('/cours/{coursId}/quiz/reponses-patients', name: 'app_quiz_reponses_patients')]
    public function voirReponsesPatients(string $coursId): Response
    {
        if (!$this->isMedecinOrAdmin()) {
            throw $this->createAccessDeniedException('Vous devez être médecin ou administrateur pour voir les réponses des patients');
        }

        $cours = $this->coursRepository->find($coursId);
        
        if (!$cours) {
            throw $this->createNotFoundException('Cours non trouvé');
        }
        
        // Get all responses grouped by patient
        $reponsesParPatient = $this->reponseRepository->findByCoursGroupedByUtilisateur($cours);
        
        // Get all questions for this course to display them
        $questions = $cours->getQuestions();
        
        return $this->render('savoir_medical/quiz_reponses_patients.html.twig', [
            'cours' => $cours,
            'reponsesParPatient' => $reponsesParPatient,
            'questions' => $questions,
        ]);
    }

    #[Route('/admin/categories-en-attente', name: 'app_admin_categories_en_attente')]
    #[IsGranted('ROLE_ADMIN')]
    public function categoriesEnAttente(): Response
    {
        $categoriesEnAttente = $this->categorieSanteRepository->findBy(
            ['statut' => \App\Enum\StatutCategorie::EN_ATTENTE],
            ['id' => 'DESC']
        );
        
        return $this->render('savoir_medical/admin_categories_en_attente.html.twig', [
            'categories' => $categoriesEnAttente,
        ]);
    }

    #[Route('/admin/categorie/{id}/approuver', name: 'app_admin_approuver_categorie', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approuverCategorie(string $id): Response
    {
        try {
            $categorieId = \Symfony\Component\Uid\Uuid::fromString($id);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $categorie = $this->categorieSanteRepository->find($categorieId);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $admin = $this->getUser();
        $categorie->setStatut(\App\Enum\StatutCategorie::APPROUVE);
        $categorie->setApprouvePar($admin);
        $categorie->setDateApprobation(new \DateTime());
        
        $this->entityManager->flush();
        
        // Send notifications
        $this->notificationService->notifierMedecinCategorieApprouvee($categorie, $admin);
        $this->notificationService->notifierPatientsNouvelleCategorie($categorie);
        
        $this->addFlash('success', 'La catégorie "' . $categorie->getNom() . '" a été approuvée avec succès !');
        
        return $this->redirectToRoute('app_admin_categories_en_attente');
    }

    #[Route('/admin/categorie/{id}/rejeter', name: 'app_admin_rejeter_categorie', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function rejeterCategorie(string $id): Response
    {
        try {
            $categorieId = \Symfony\Component\Uid\Uuid::fromString($id);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $categorie = $this->categorieSanteRepository->find($categorieId);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $admin = $this->getUser();
        $categorie->setStatut(\App\Enum\StatutCategorie::REJETE);
        $categorie->setApprouvePar($admin);
        $categorie->setDateApprobation(new \DateTime());
        
        $this->entityManager->flush();
        
        // Send notification to doctor
        $this->notificationService->notifierMedecinCategorieRejetee($categorie, $admin);
        
        $this->addFlash('warning', 'La catégorie "' . $categorie->getNom() . '" a été rejetée.');
        
        return $this->redirectToRoute('app_admin_categories_en_attente');
    }

}
