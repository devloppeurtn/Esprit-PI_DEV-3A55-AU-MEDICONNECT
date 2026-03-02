<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service d'intégration avec l'API Groq pour les recommandations IA
 * Utilise le modèle Llama pour générer des recommandations personnalisées
 */
class GroqAIService
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile'; // Modèle Llama optimisé pour Groq
    private const MAX_TOKENS = 500;
    private const TEMPERATURE = 0.7;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $groqApiKey
    ) {
    }

    /**
     * Génère des recommandations d'événements personnalisées via l'API Groq
     * 
     * @param array $patientProfile Profil médical du patient (anonymisé)
     * @param array $availableEvents Liste des événements disponibles
     * @return array Recommandations avec scores et explications
     */
    public function generateEventRecommendations(array $patientProfile, array $availableEvents): array
    {
        if (empty($this->groqApiKey) || $this->groqApiKey === 'your_groq_api_key_here') {
            $this->logger->warning('Groq API key not configured, using fallback recommendations');
            return $this->getFallbackRecommendations($availableEvents);
        }

        try {
            $prompt = $this->buildPrompt($patientProfile, $availableEvents);
            
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant médical IA spécialisé dans la recommandation d\'événements de santé personnalisés. Tu analyses les profils médicaux anonymisés et suggères les événements les plus pertinents. Réponds UNIQUEMENT en JSON valide.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => self::TEMPERATURE,
                    'max_tokens' => self::MAX_TOKENS,
                    'response_format' => ['type' => 'json_object']
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            
            if (isset($data['choices'][0]['message']['content'])) {
                $aiResponse = json_decode($data['choices'][0]['message']['content'], true);
                return $this->parseAIResponse($aiResponse, $availableEvents);
            }

            $this->logger->error('Invalid Groq API response format');
            return $this->getFallbackRecommendations($availableEvents);

        } catch (\Exception $e) {
            $this->logger->error('Groq API error: ' . $e->getMessage());
            return $this->getFallbackRecommendations($availableEvents);
        }
    }

    /**
     * Construit le prompt pour l'API Groq
     */
    private function buildPrompt(array $patientProfile, array $availableEvents): string
    {
        $eventsJson = [];
        foreach ($availableEvents as $event) {
            $eventsJson[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'content' => substr($event->getContent() ?? '', 0, 200),
                'date' => $event->getEventDate()?->format('Y-m-d'),
                'location' => $event->getLocation()
            ];
        }

        $profileDescription = $this->buildProfileDescription($patientProfile);

        return <<<PROMPT
Analyse ce profil médical anonymisé et recommande les 3 événements les plus pertinents parmi la liste fournie.

PROFIL PATIENT (anonymisé):
{$profileDescription}

ÉVÉNEMENTS DISPONIBLES:
{$this->formatEventsForPrompt($eventsJson)}

INSTRUCTIONS:
1. Analyse le profil médical du patient
2. Pour chaque événement, évalue sa pertinence (score de 0 à 100)
3. Sélectionne les 3 événements les plus pertinents
4. Explique pourquoi chaque événement est recommandé

RÉPONDS EN JSON avec cette structure exacte:
{
  "recommendations": [
    {
      "event_id": "uuid-de-l-evenement",
      "score": 85,
      "reasons": ["Raison 1", "Raison 2"]
    }
  ]
}

IMPORTANT: Réponds UNIQUEMENT avec du JSON valide, sans texte avant ou après.
PROMPT;
    }

    /**
     * Construit une description du profil patient pour le prompt
     */
    private function buildProfileDescription(array $profile): string
    {
        $parts = [];

        if (!empty($profile['age'])) {
            $parts[] = "Âge: {$profile['age']} ans";
        }

        if (!empty($profile['gender'])) {
            $genderText = $profile['gender'] === 'F' ? 'Femme' : 'Homme';
            $parts[] = "Genre: {$genderText}";
        }

        if (!empty($profile['chronic_diseases'])) {
            $parts[] = "Maladies chroniques: {$profile['chronic_diseases']}";
        }

        if (!empty($profile['allergies'])) {
            $parts[] = "Allergies: {$profile['allergies']}";
        }

        if (!empty($profile['recent_consultations'])) {
            $parts[] = "Consultations récentes: " . implode(', ', $profile['recent_consultations']);
        }

        if (!empty($profile['medications'])) {
            $parts[] = "Médicaments actuels: " . implode(', ', $profile['medications']);
        }

        return implode("\n", $parts);
    }

    /**
     * Formate les événements pour le prompt
     */
    private function formatEventsForPrompt(array $events): string
    {
        $formatted = [];
        foreach ($events as $index => $event) {
            $formatted[] = sprintf(
                "%d. [ID: %s] %s\n   Date: %s\n   Description: %s",
                $index + 1,
                $event['id'],
                $event['title'],
                $event['date'] ?? 'Non définie',
                $event['content'] ?? 'Pas de description'
            );
        }
        return implode("\n\n", $formatted);
    }

    /**
     * Parse la réponse de l'IA et retourne les recommandations formatées
     */
    private function parseAIResponse(?array $aiResponse, array $availableEvents): array
    {
        if (!isset($aiResponse['recommendations']) || !is_array($aiResponse['recommendations'])) {
            return $this->getFallbackRecommendations($availableEvents);
        }

        $recommendations = [];
        $eventMap = [];
        
        // Créer un map des événements par ID
        foreach ($availableEvents as $event) {
            $eventMap[$event->getId()] = $event;
        }

        foreach ($aiResponse['recommendations'] as $rec) {
            if (!isset($rec['event_id']) || !isset($eventMap[$rec['event_id']])) {
                continue;
            }

            $recommendations[] = [
                'event' => $eventMap[$rec['event_id']],
                'score' => $rec['score'] ?? 50,
                'reasons' => $rec['reasons'] ?? ['Recommandé par l\'IA'],
                'ai_generated' => true
            ];
        }

        // Si l'IA n'a pas retourné assez de recommandations, compléter avec le fallback
        if (count($recommendations) < 3) {
            $fallback = $this->getFallbackRecommendations($availableEvents, 3 - count($recommendations));
            $recommendations = array_merge($recommendations, $fallback);
        }

        return $recommendations;
    }

    /**
     * Recommandations de secours si l'API Groq n'est pas disponible
     */
    private function getFallbackRecommendations(array $availableEvents, int $limit = 3): array
    {
        $recommendations = [];
        $count = 0;

        foreach ($availableEvents as $event) {
            if ($count >= $limit) {
                break;
            }

            $recommendations[] = [
                'event' => $event,
                'score' => 50,
                'reasons' => ['Événement de santé recommandé'],
                'ai_generated' => false
            ];
            $count++;
        }

        return $recommendations;
    }

    /**
     * Vérifie si l'API Groq est configurée et disponible
     */
    public function isAvailable(): bool
    {
        return !empty($this->groqApiKey) && $this->groqApiKey !== 'your_groq_api_key_here';
    }
}
