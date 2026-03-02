<?php

namespace App\Service;

use App\Entity\Patient;
use App\Entity\Evenement;
use App\Enum\StatutEvenement;
use App\Repository\EvenementRepository;

class EventRecommendationService
{
    // Mots-clés médicaux et leurs synonymes pour la correspondance (fallback)
    private const MEDICAL_KEYWORDS = [
        'diabète' => ['diabète', 'diabete', 'glycémie', 'insuline', 'sucre', 'glucose'],
        'hypertension' => ['hypertension', 'tension', 'pression artérielle', 'cardiovasculaire'],
        'asthme' => ['asthme', 'respiratoire', 'poumon', 'bronches'],
        'allergie' => ['allergie', 'allergique', 'réaction'],
        'cardiaque' => ['cardiaque', 'cœur', 'coeur', 'cardiovasculaire', 'cardiologie'],
        'cancer' => ['cancer', 'oncologie', 'tumeur', 'chimiothérapie'],
        'obésité' => ['obésité', 'obesite', 'poids', 'surpoids', 'nutrition'],
        'dépression' => ['dépression', 'depression', 'anxiété', 'mental', 'psychologique'],
        'arthrite' => ['arthrite', 'rhumatisme', 'articulation', 'douleur'],
        'thyroïde' => ['thyroïde', 'thyroide', 'hormonal', 'endocrinologie'],
    ];

    public function __construct(
        private EvenementRepository $evenementRepository,
        private GroqAIService $groqAIService
    ) {
    }

    /**
     * Suggère des événements personnalisés pour un patient basés sur son dossier médical
     * Utilise l'API Groq avec Llama pour des recommandations intelligentes
     * 
     * @param Patient $patient
     * @param int $limit Nombre maximum de suggestions
     * @return array Tableau d'événements avec score de pertinence
     */
    public function getSuggestedEvents(Patient $patient, int $limit = 5): array
    {
        // Récupérer tous les événements validés et actifs
        $allEvents = $this->evenementRepository->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->andWhere('e.isActive = :active')
            ->andWhere('e.eventDate >= :today')
            ->setParameter('statut', StatutEvenement::VALIDE)
            ->setParameter('active', true)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();

        if (empty($allEvents)) {
            return [];
        }

        // Extraire le profil médical du patient
        $medicalProfile = $this->extractMedicalProfile($patient);

        // Utiliser l'API Groq si disponible, sinon utiliser le système de mots-clés
        if ($this->groqAIService->isAvailable()) {
            $scoredEvents = $this->groqAIService->generateEventRecommendations($medicalProfile, $allEvents);
        } else {
            // Fallback: système de mots-clés classique
            $scoredEvents = $this->getKeywordBasedRecommendations($allEvents, $medicalProfile);
        }

        // Trier par score décroissant
        usort($scoredEvents, fn($a, $b) => $b['score'] <=> $a['score']);

        // Retourner les N meilleurs
        return array_slice($scoredEvents, 0, $limit);
    }

    /**
     * Extrait le profil médical du patient (anonymisé pour l'IA)
     */
    private function extractMedicalProfile(Patient $patient): array
    {
        $profile = [
            'keywords' => [],
            'conditions' => [],
            'age' => null,
            'gender' => null,
            'chronic_diseases' => null,
            'allergies' => null,
            'recent_consultations' => [],
            'medications' => []
        ];

        // Calculer l'âge
        if ($patient->getDateNaissance()) {
            $now = new \DateTime();
            $birthDate = \DateTime::createFromInterface($patient->getDateNaissance());
            $profile['age'] = $now->diff($birthDate)->y;
        }

        // Genre (pour recommandations comme Octobre Rose)
        // Note: Vous devrez ajouter un champ genre dans l'entité Patient si nécessaire
        // $profile['gender'] = $patient->getGenre(); // 'M' ou 'F'

        $dossier = $patient->getDossierMedical();
        if (!$dossier) {
            return $profile;
        }

        // Extraire les maladies chroniques
        if ($maladies = $dossier->getMaladiesChroniques()) {
            $profile['chronic_diseases'] = $maladies;
            $profile['conditions'][] = $maladies;
            $profile['keywords'] = array_merge(
                $profile['keywords'],
                $this->extractKeywords($maladies)
            );
        }

        // Extraire les allergies
        if ($allergies = $dossier->getAllergies()) {
            $profile['allergies'] = $allergies;
            $profile['conditions'][] = $allergies;
            $profile['keywords'] = array_merge(
                $profile['keywords'],
                $this->extractKeywords($allergies)
            );
        }

        // Extraire des consultations récentes
        $recentConsultations = $dossier->getConsultations()
            ->filter(function($c) {
                $consultationDate = $c->getDate();
                if (!$consultationDate) {
                    return false;
                }
                // Convert DateTime to DateTimeImmutable for comparison
                $consultationDateImmutable = \DateTimeImmutable::createFromMutable($consultationDate);
                return $consultationDateImmutable > new \DateTimeImmutable('-6 months');
            })
            ->toArray();

        foreach ($recentConsultations as $consultation) {
            if ($diagnostic = $consultation->getDiagnostic()) {
                $profile['conditions'][] = $diagnostic;
                $profile['recent_consultations'][] = $diagnostic;
                $profile['keywords'] = array_merge(
                    $profile['keywords'],
                    $this->extractKeywords($diagnostic)
                );
            }
        }

        // Extraire les médicaments actuels
        foreach ($dossier->getMedicamentsActuels() as $medicament) {
            if ($nom = $medicament->getNom()) {
                $profile['medications'][] = $nom;
            }
        }

        // Dédupliquer les mots-clés
        $profile['keywords'] = array_unique($profile['keywords']);

        return $profile;
    }

    /**
     * Recommandations basées sur les mots-clés (fallback si Groq n'est pas disponible)
     */
    private function getKeywordBasedRecommendations(array $allEvents, array $medicalProfile): array
    {
        $scoredEvents = [];
        foreach ($allEvents as $event) {
            $score = $this->calculateRelevanceScore($event, $medicalProfile);
            if ($score > 0) {
                $scoredEvents[] = [
                    'event' => $event,
                    'score' => $score,
                    'reasons' => $this->getMatchReasons($event, $medicalProfile),
                    'ai_generated' => false
                ];
            }
        }
        return $scoredEvents;
    }

    /**
     * Extrait les mots-clés médicaux d'un texte
     */
    private function extractKeywords(string $text): array
    {
        $text = mb_strtolower($text);
        $keywords = [];

        foreach (self::MEDICAL_KEYWORDS as $category => $synonyms) {
            foreach ($synonyms as $synonym) {
                if (str_contains($text, $synonym)) {
                    $keywords[] = $category;
                    break;
                }
            }
        }

        return $keywords;
    }

    /**
     * Calcule le score de pertinence d'un événement pour un profil médical
     */
    private function calculateRelevanceScore(Evenement $event, array $medicalProfile): int
    {
        $score = 0;
        $eventText = mb_strtolower($event->getTitle() . ' ' . ($event->getContent() ?? ''));

        // Correspondance directe avec les mots-clés du profil
        foreach ($medicalProfile['keywords'] as $keyword) {
            if (isset(self::MEDICAL_KEYWORDS[$keyword])) {
                foreach (self::MEDICAL_KEYWORDS[$keyword] as $synonym) {
                    if (str_contains($eventText, $synonym)) {
                        $score += 10; // Score élevé pour correspondance directe
                        break;
                    }
                }
            }
        }

        // Correspondance partielle avec les conditions
        foreach ($medicalProfile['conditions'] as $condition) {
            $conditionWords = explode(' ', mb_strtolower($condition));
            foreach ($conditionWords as $word) {
                if (strlen($word) > 4 && str_contains($eventText, $word)) {
                    $score += 3; // Score moyen pour correspondance partielle
                }
            }
        }

        // Bonus pour les événements de prévention et sensibilisation
        if (str_contains($eventText, 'prévention') || 
            str_contains($eventText, 'sensibilisation') ||
            str_contains($eventText, 'dépistage')) {
            $score += 2;
        }

        return $score;
    }

    /**
     * Génère les raisons de la recommandation
     */
    private function getMatchReasons(Evenement $event, array $medicalProfile): array
    {
        $reasons = [];
        $eventText = mb_strtolower($event->getTitle() . ' ' . ($event->getContent() ?? ''));

        foreach ($medicalProfile['keywords'] as $keyword) {
            if (isset(self::MEDICAL_KEYWORDS[$keyword])) {
                foreach (self::MEDICAL_KEYWORDS[$keyword] as $synonym) {
                    if (str_contains($eventText, $synonym)) {
                        $reasons[] = "Correspond à votre condition: " . ucfirst($keyword);
                        break;
                    }
                }
            }
        }

        if (empty($reasons)) {
            $reasons[] = "Événement de santé recommandé";
        }

        return array_unique($reasons);
    }

    /**
     * Génère une explication personnalisée pour la recommandation
     */
    public function getRecommendationExplanation(Evenement $event, Patient $patient): string
    {
        $medicalProfile = $this->extractMedicalProfile($patient);
        $reasons = $this->getMatchReasons($event, $medicalProfile);

        if (empty($reasons)) {
            return "Cet événement pourrait vous intéresser pour votre santé.";
        }

        return "Recommandé car: " . implode(', ', $reasons);
    }
}
