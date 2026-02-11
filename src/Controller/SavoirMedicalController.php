<?php

namespace App\Controller;

use App\Entity\CategorieSante;
use App\Entity\ProgressionUtilisateur;
use App\Entity\CoursEducatif;
use App\Form\CategorieSanteFormType;
use App\Repository\CategorieSanteRepository;
use App\Repository\ProgressionUtilisateurRepository;
use App\Repository\CoursEducatifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/savoir-medical')]
#[IsGranted('ROLE_USER')]
class SavoirMedicalController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategorieSanteRepository $categorieSanteRepository,
        private ProgressionUtilisateurRepository $progressionRepository,
        private CoursEducatifRepository $coursRepository
    ) {
    }

    #[Route('/', name: 'app_savoir_medical_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Récupérer toutes les catégories
        $categories = $this->categorieSanteRepository->findAll();
        
        // Récupérer les progressions de l'utilisateur
        $progressions = $this->progressionRepository->findByUtilisateur($user);
        
        // Créer un tableau associatif pour accès rapide
        $progressionsParCategorie = [];
        foreach ($progressions as $progression) {
            $progressionsParCategorie[$progression->getCategorieSante()->getId()->toRfc4122()] = $progression;
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
        
        // Récupérer la progression de l'utilisateur pour cette catégorie
        $progression = $this->progressionRepository->findByUtilisateurAndCategorie($user, $categorie);
        
        return $this->render('savoir_medical/categorie.html.twig', [
            'categorie' => $categorie,
            'cours' => $cours,
            'progression' => $progression,
        ]);
    }

    #[Route('/progression', name: 'app_progression_utilisateur')]
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
            if ($progression->getBadgeNom()) {
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
        
        // Récupérer la progression pour cette catégorie
        $progression = $this->progressionRepository->findByUtilisateurAndCategorie(
            $user,
            $cours->getCategorieSante()
        );
        
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
}
