<?php

namespace App\SavoirMedicalBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service pour l'intégration de l'API Groq AI
 * Génère des questions de quiz basées sur le contenu des cours
 */
class GroqAIService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_API_KEY = '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';
    private const MODEL = 'llama-3.3-70b-versatile'; // Updated model
    
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    /**
     * Génère une question de quiz basée sur le contenu d'un cours
     * 
     * @param string $coursContent Le contenu du cours éducatif
     * @param string $categorieName Le nom de la catégorie
     * @param int $questionNumber Le numéro de la question (pour la variété)
     * @return array Question générée avec réponses et bonne réponse
     */
    public function generateQuizQuestion(string $coursContent, string $categorieName, int $questionNumber = 1): array
    {
        // Limit content length to avoid API errors
        $maxLength = 2000;
        if (strlen($coursContent) > $maxLength) {
            $coursContent = substr($coursContent, 0, $maxLength) . '...';
        }
        
        $prompt = $this->buildPrompt($coursContent, $categorieName, $questionNumber);
        
        try {
            $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . self::GROQ_API_KEY,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert médical qui crée des questions de quiz pédagogiques. Chaque question doit être COMPLÈTEMENT UNIQUE et aborder un aspect TOTALEMENT DIFFÉRENT du sujet. INTERDICTION de répéter des questions similaires.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 1.0, // Maximum creativity
                    'max_tokens' => 500,
                    'top_p' => 0.95, // Add nucleus sampling for more variety
                    'frequency_penalty' => 1.5, // Penalize repetition heavily
                    'presence_penalty' => 1.5, // Encourage new topics
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false); // false = don't throw on error status
            
            // Log the raw response for debugging
            error_log('Groq API Status: ' . $statusCode);
            error_log('Groq API Response: ' . json_encode($data));
            
            if ($statusCode !== 200) {
                $errorMessage = $data['error']['message'] ?? 'Unknown error';
                throw new \RuntimeException('Groq API Error: ' . $errorMessage);
            }
            
            $parsed = $this->parseAIResponse($data);
            
            // Log parsed data
            error_log('Parsed Question Data: ' . json_encode($parsed));
            
            return $parsed;
            
        } catch (\Exception $e) {
            error_log('Groq API Error: ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de la génération de la question: ' . $e->getMessage());
        }
    }

    /**
     * Construit le prompt pour l'IA
     */
    private function buildPrompt(string $coursContent, string $categorieName, int $questionNumber = 1): string
    {
        // Add variety instructions based on question number
        $varietyInstructions = [
            "Concentre-toi UNIQUEMENT sur les concepts fondamentaux et les définitions de base",
            "Pose une question UNIQUEMENT sur les détails techniques et les mécanismes spécifiques",
            "Crée une question UNIQUEMENT sur les applications pratiques et les cas d'usage",
            "Génère une question UNIQUEMENT sur les causes, origines et facteurs de risque",
            "Formule une question UNIQUEMENT sur les comparaisons et les différences",
            "Pose une question UNIQUEMENT sur les symptômes, signes cliniques et manifestations",
            "Crée une question UNIQUEMENT sur les méthodes de diagnostic et d'examen",
            "Génère une question UNIQUEMENT sur les options de traitement et thérapies",
            "Formule une question UNIQUEMENT sur la prévention et les mesures préventives",
            "Pose une question UNIQUEMENT sur les complications, pronostic et évolution"
        ];
        
        $variety = $varietyInstructions[($questionNumber - 1) % count($varietyInstructions)];
        
        // Add random seed to force different responses
        $randomSeed = time() + $questionNumber + rand(1, 1000);
        
        return <<<PROMPT
[Seed: {$randomSeed}] Basé sur le cours suivant de la catégorie "{$categorieName}":

{$coursContent}

INSTRUCTION SPÉCIFIQUE #{$questionNumber}: {$variety}

Génère UNE question de quiz COMPLÈTEMENT DIFFÉRENTE des autres avec:
1. Une question claire et précise sur l'aspect spécifique demandé ci-dessus
2. Quatre options de réponse (A, B, C, D) - toutes plausibles
3. La lettre de la bonne réponse
4. Une explication courte

CRITIQUE: Ne répète JAMAIS une question similaire. Chaque question doit explorer un ANGLE TOTALEMENT DIFFÉRENT du sujet.

Format de réponse attendu:
QUESTION: [ta question unique]
A) [option A]
B) [option B]
C) [option C]
D) [option D]
REPONSE: [A, B, C ou D]
EXPLICATION: [explication courte]
PROMPT;
    }

    /**
     * Parse la réponse de l'IA
     */
    private function parseAIResponse(array $data): array
    {
        $content = $data['choices'][0]['message']['content'] ?? '';
        
        // Extraction des informations
        preg_match('/QUESTION:\s*(.+?)(?=A\))/s', $content, $questionMatch);
        preg_match('/A\)\s*(.+?)(?=B\))/s', $content, $optionAMatch);
        preg_match('/B\)\s*(.+?)(?=C\))/s', $content, $optionBMatch);
        preg_match('/C\)\s*(.+?)(?=D\))/s', $content, $optionCMatch);
        preg_match('/D\)\s*(.+?)(?=REPONSE:)/s', $content, $optionDMatch);
        preg_match('/REPONSE:\s*([A-D])/i', $content, $answerMatch);
        preg_match('/EXPLICATION:\s*(.+?)$/s', $content, $explanationMatch);

        return [
            'question' => trim($questionMatch[1] ?? ''),
            'options' => [
                'A' => trim($optionAMatch[1] ?? ''),
                'B' => trim($optionBMatch[1] ?? ''),
                'C' => trim($optionCMatch[1] ?? ''),
                'D' => trim($optionDMatch[1] ?? ''),
            ],
            'correct_answer' => strtoupper(trim($answerMatch[1] ?? 'A')),
            'explanation' => trim($explanationMatch[1] ?? ''),
            'raw_content' => $content,
        ];
    }

    /**
     * Génère plusieurs questions en une seule fois
     * 
     * @param string $coursContent Le contenu du cours
     * @param string $categorieName Le nom de la catégorie
     * @param int $count Nombre de questions à générer
     * @return array Tableau de questions générées
     */
    public function generateMultipleQuestions(string $coursContent, string $categorieName, int $count = 5): array
    {
        $questions = [];
        
        for ($i = 0; $i < $count; $i++) {
            try {
                $questions[] = $this->generateQuizQuestion($coursContent, $categorieName, $i + 1);
                // Petit délai pour éviter le rate limiting
                usleep(500000); // 0.5 secondes
            } catch (\Exception $e) {
                // Continue même si une question échoue
                continue;
            }
        }
        
        return $questions;
    }
}
