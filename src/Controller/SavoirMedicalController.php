<?php

namespace App\Controller;

use App\Entity\CategorieSante;
use App\Entity\ProgressionUtilisateur;
use App\Entity\CoursEducatif;
<<<<<<< HEAD
=======
use App\Entity\Medecin;
use App\Entity\Utilisateur;
use App\Entity\ReponseUtilisateur;
use App\Entity\QuestionQuiz;
use App\Enum\StatutQuestion;
>>>>>>> isramedi
use App\Form\CategorieSanteFormType;
use App\Repository\CategorieSanteRepository;
use App\Repository\ProgressionUtilisateurRepository;
use App\Repository\CoursEducatifRepository;
<<<<<<< HEAD
=======
use App\Repository\ReponseUtilisateurRepository;
use App\Service\QuizAIGenerator;
>>>>>>> isramedi
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
<<<<<<< HEAD
=======
use Symfony\Component\Uid\Uuid;
>>>>>>> isramedi

#[Route('/savoir-medical')]
#[IsGranted('ROLE_USER')]
class SavoirMedicalController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategorieSanteRepository $categorieSanteRepository,
        private ProgressionUtilisateurRepository $progressionRepository,
<<<<<<< HEAD
        private CoursEducatifRepository $coursRepository
=======
        private CoursEducatifRepository $coursRepository,
        private ReponseUtilisateurRepository $reponseRepository
>>>>>>> isramedi
    ) {
    }

    #[Route('/', name: 'app_savoir_medical_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Récupérer toutes les catégories
        $categories = $this->categorieSanteRepository->findAll();
        
<<<<<<< HEAD
        // Récupérer les progressions de l'utilisateur
        $progressions = $this->progressionRepository->findByUtilisateur($user);
        
        // Créer un tableau associatif pour accès rapide
        $progressionsParCategorie = [];
        foreach ($progressions as $progression) {
            $progressionsParCategorie[$progression->getCategorieSante()->getId()->toRfc4122()] = $progression;
=======
        // Récupérer les progressions de l'utilisateur (uniquement pour les patients)
        $progressionsParCategorie = [];
        if ($this->isGranted('ROLE_PATIENT')) {
            $progressions = $this->progressionRepository->findByUtilisateur($user);
            foreach ($progressions as $progression) {
                $progressionsParCategorie[$progression->getCategorieSante()->getId()->toRfc4122()] = $progression;
            }
>>>>>>> isramedi
        }
        
        return $this->render('savoir_medical/index.html.twig', [
            'categories' => $categories,
            'progressionsParCategorie' => $progressionsParCategorie,
        ]);
    }

    #[Route('/categorie/nouvelle', name: 'app_savoir_medical_nouvelle_categorie')]
    #[IsGranted('ROLE_MEDECIN')]
    public function nouvelleCategorie(Request $request): Response
    {
        $categorie = new CategorieSante();
        $form = $this->createForm(CategorieSanteFormType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($categorie);
            $this->entityManager->flush();

            $this->addFlash('success', 'La catégorie a été créée avec succès !');
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
        
<<<<<<< HEAD
        // Récupérer la progression de l'utilisateur pour cette catégorie
        $progression = $this->progressionRepository->findByUtilisateurAndCategorie($user, $categorie);
=======
        // Récupérer la progression de l'utilisateur pour cette catégorie (uniquement pour les patients)
        $progression = null;
        if ($this->isGranted('ROLE_PATIENT')) {
            $progression = $this->progressionRepository->findByUtilisateurAndCategorie($user, $categorie);
        }
>>>>>>> isramedi
        
        return $this->render('savoir_medical/categorie.html.twig', [
            'categorie' => $categorie,
            'cours' => $cours,
            'progression' => $progression,
        ]);
    }

    #[Route('/progression', name: 'app_progression_utilisateur')]
<<<<<<< HEAD
=======
    #[IsGranted('ROLE_PATIENT')]
>>>>>>> isramedi
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
<<<<<<< HEAD
            if ($progression->getBadgeNom()) {
=======
            if ($progression->getBadgeNom() && $progression->getCategorieSante()) {
>>>>>>> isramedi
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
        
<<<<<<< HEAD
        // Récupérer la progression pour cette catégorie
        $progression = $this->progressionRepository->findByUtilisateurAndCategorie(
            $user,
            $cours->getCategorieSante()
        );
=======
        // Récupérer la progression pour cette catégorie (uniquement pour les patients)
        $progression = null;
        if ($this->isGranted('ROLE_PATIENT')) {
            $progression = $this->progressionRepository->findByUtilisateurAndCategorie(
                $user,
                $cours->getCategorieSante()
            );
        }
>>>>>>> isramedi
        
        // Récupérer les questions du cours
        $questions = $cours->getQuestions();
        
        return $this->render('savoir_medical/cours.html.twig', [
            'cours' => $cours,
            'progression' => $progression,
            'questions' => $questions,
        ]);
    }

    #[Route('/categorie/{id}/modifier', name: 'app_savoir_medical_modifier_categorie')]
    #[IsGranted('ROLE_MEDECIN')]
    public function modifierCategorie(Request $request, CategorieSante $categorie): Response
    {
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
    #[IsGranted('ROLE_MEDECIN')]
    public function supprimerCategorie(Request $request, CategorieSante $categorie): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categorie->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($categorie);
            $this->entityManager->flush();

            $this->addFlash('success', 'La catégorie a été supprimée avec succès !');
        }

        return $this->redirectToRoute('app_savoir_medical_index');
    }
<<<<<<< HEAD
=======

    #[Route('/categorie/{categorieId}/cours/nouveau', name: 'app_savoir_medical_nouveau_cours')]
    #[IsGranted('ROLE_MEDECIN')]
    public function nouveauCours(Request $request, string $categorieId): Response
    {
        $categorie = $this->categorieSanteRepository->find($categorieId);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }
        
        $cours = new CoursEducatif();
        $cours->setCategorieSante($categorie);
        
        // Set the current médecin as validator
        $user = $this->getUser();
        if ($user instanceof Medecin) {
            $cours->setMedecinValidateur($user);
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
    #[IsGranted('ROLE_MEDECIN')]
    public function modifierCours(Request $request, CoursEducatif $cours): Response
    {
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
    #[IsGranted('ROLE_MEDECIN')]
    public function supprimerCours(Request $request, CoursEducatif $cours): Response
    {
        $categorieId = $cours->getCategorieSante()->getId();
        
        if ($this->isCsrfTokenValid('delete'.$cours->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($cours);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le cours a été supprimé avec succès !');
        }
        
        return $this->redirectToRoute('app_savoir_medical_categorie', ['id' => $categorieId]);
    }

    #[Route('/cours/{coursId}/quiz/gerer', name: 'app_quiz_gerer')]
    #[IsGranted('ROLE_MEDECIN')]
    public function gererQuiz(string $coursId): Response
    {
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
    #[IsGranted('ROLE_MEDECIN')]
    public function genererQuizIA(Request $request, string $coursId, QuizAIGenerator $aiGenerator): Response
    {
        $cours = $this->coursRepository->find($coursId);
        
        if (!$cours) {
            throw $this->createNotFoundException('Cours non trouvé');
        }
        
        try {
            $nombreQuestions = (int) $request->request->get('nombreQuestions', 10);
            $difficulte = $request->request->get('difficulte', 'moyen');
            
            // Generate questions using AI
            $questions = $aiGenerator->genererQuestions($cours, $nombreQuestions, $difficulte);
            
            // Save questions to database
            foreach ($questions as $question) {
                $this->entityManager->persist($question);
            }
            $this->entityManager->flush();
            
            $this->addFlash('success', count($questions) . ' questions générées avec succès ! Vous pouvez les réviser et les valider.');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_quiz_gerer', ['coursId' => $coursId]);
    }
    
    #[Route('/question/{questionId}/supprimer', name: 'app_quiz_supprimer_question', methods: ['POST'])]
    #[IsGranted('ROLE_MEDECIN')]
    public function supprimerQuestion(Request $request, string $questionId): Response
    {
        $question = $this->entityManager->getRepository(QuestionQuiz::class)->find($questionId);
        
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
    #[IsGranted('ROLE_MEDECIN')]
    public function validerQuestion(Request $request, string $questionId): Response
    {
        $question = $this->entityManager->getRepository(QuestionQuiz::class)->find($questionId);
        
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
            }
        }
        
        return $this->redirectToRoute('app_quiz_gerer', ['coursId' => $coursId]);
    }
    
    #[Route('/question/{questionId}/modifier', name: 'app_quiz_modifier_question')]
    #[IsGranted('ROLE_MEDECIN')]
    public function modifierQuestion(Request $request, string $questionId): Response
    {
        $question = $this->entityManager->getRepository(QuestionQuiz::class)->find($questionId);
        
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
    #[IsGranted('ROLE_MEDECIN')]
    public function previewQuiz(string $coursId): Response
    {
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
        
        // Get ALL questions for this course first
        $allQuestions = $this->entityManager->getRepository(QuestionQuiz::class)
            ->findBy(['coursEducatif' => $cours]);
        
        // Filter only validated questions
        $questions = array_filter($allQuestions, function($q) {
            return $q->getStatut() === StatutQuestion::VALIDE_MEDECIN;
        });
        
        return $this->render('savoir_medical/quiz_prendre.html.twig', [
            'cours' => $cours,
            'questions' => array_values($questions), // Re-index array
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
    #[IsGranted('ROLE_MEDECIN')]
    public function ajouterQuestionManuel(Request $request, string $coursId): Response
    {
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
    #[IsGranted('ROLE_MEDECIN')]
    public function voirReponsesPatients(string $coursId): Response
    {
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
>>>>>>> isramedi
}
