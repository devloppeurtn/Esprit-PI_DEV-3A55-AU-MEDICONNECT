<?php

namespace App\Service;

use App\Entity\CoursEducatif;
use App\Entity\QuestionQuiz;
use App\Enum\StatutQuestion;

class QuizAIGenerator
{
    private ?string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    private bool $useMockData = true; // Set to false when you have a real API key

    public function __construct()
    {
        // Get API key from environment variable
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        
        // Use mock data if no API key is configured
        if (!$this->apiKey) {
            $this->useMockData = true;
        }
    }

    /**
     * Generate quiz questions using AI (or mock data)
     */
    public function genererQuestions(CoursEducatif $cours, int $nombre = 10, string $difficulte = 'moyen'): array
    {
        // Use mock generator if no API key
        if ($this->useMockData) {
            return $this->genererQuestionsMock($cours, $nombre, $difficulte);
        }

        $prompt = $this->construirePrompt($cours, $nombre, $difficulte);
        
        $response = $this->appellerAPI($prompt);
        
        return $this->parserReponse($response, $cours);
    }

    /**
     * Generate realistic mock questions based on course content
     */
    private function genererQuestionsMock(CoursEducatif $cours, int $nombre, string $difficulte): array
    {
        $questions = [];
        $contenu = $cours->getContenu();
        $titre = $cours->getTitre();
        
        // Extract key medical terms from course content
        $mots = $this->extraireMots($contenu);
        
        // Question templates based on difficulty
        $templates = $this->getTemplatesParDifficulte($difficulte);
        
        for ($i = 0; $i < $nombre; $i++) {
            $template = $templates[array_rand($templates)];
            $motCle = $mots[array_rand($mots)] ?? $titre;
            
            $question = new QuestionQuiz();
            $question->setCoursEducatif($cours);
            $question->setEnonce($this->genererEnonce($template, $motCle, $titre));
            
            // Generate options with correct answer at random position
            $optionsData = $this->genererOptions($template, $motCle, $difficulte);
            $options = $optionsData['options'];
            $correctAnswer = $optionsData['correct'];
            
            $question->setOptionsReponsesArray($options);
            $question->setReponseCorrecte($correctAnswer);
            
            $question->setExplication($this->genererExplication($template, $motCle));
            $question->setStatut(StatutQuestion::IA_PROPOSE);
            
            $questions[] = $question;
        }
        
        return $questions;
    }

    private function extraireMots(string $texte): array
    {
        // Extract meaningful medical terms (words longer than 5 characters)
        preg_match_all('/\b[A-Za-zÀ-ÿ]{6,}\b/u', $texte, $matches);
        $mots = array_unique($matches[0]);
        return array_slice($mots, 0, 20); // Limit to 20 terms
    }

    private function getTemplatesParDifficulte(string $difficulte): array
    {
        $templates = [
            'facile' => [
                'definition' => 'Qu\'est-ce que {terme} ?',
                'identification' => 'Quel est le rôle principal de {terme} ?',
                'caracteristique' => 'Quelle est une caractéristique de {terme} ?',
            ],
            'moyen' => [
                'analyse' => 'Comment {terme} affecte-t-il le système médical ?',
                'comparaison' => 'Quelle est la différence entre {terme} et d\'autres approches ?',
                'application' => 'Dans quel contexte utilise-t-on {terme} ?',
            ],
            'difficile' => [
                'synthese' => 'Analysez l\'impact de {terme} sur la pratique médicale moderne',
                'evaluation' => 'Évaluez l\'efficacité de {terme} dans le traitement',
                'critique' => 'Quelles sont les limites de {terme} ?',
            ]
        ];
        
        return $templates[$difficulte] ?? $templates['moyen'];
    }

    private function genererEnonce(string $template, string $motCle, string $titre): string
    {
        $enonce = str_replace('{terme}', $motCle, $template);
        
        // Add context from course title
        if (rand(0, 1)) {
            $enonce .= " dans le contexte de " . strtolower($titre) . " ?";
        }
        
        return ucfirst($enonce);
    }

    private function genererOptions(string $template, string $motCle, string $difficulte): array
    {
        $correctAnswers = [
            // Correct-style answers
            "C'est un élément essentiel qui joue un rôle crucial dans le diagnostic et le traitement",
            "Il s'agit d'une approche thérapeutique validée par des études cliniques",
            "C'est une méthode diagnostique permettant d'identifier les pathologies",
            "Il représente un facteur important dans la prise en charge des patients",
            "C'est une technique fondamentale utilisée en pratique médicale moderne",
            "Il s'agit d'un concept clé dans la compréhension des pathologies",
        ];
        
        $incorrectAnswers = [
            // Plausible but incorrect
            "C'est une technique obsolète qui n'est plus utilisée en pratique moderne",
            "Il s'agit d'un concept théorique sans application clinique directe",
            "C'est une approche controversée avec des résultats non concluants",
            "Il représente une alternative non validée scientifiquement",
            "C'est une méthode expérimentale sans preuves suffisantes",
            "Il s'agit d'une pratique déconseillée par les autorités de santé",
            "C'est une théorie dépassée remplacée par des approches plus modernes",
            "Il représente un risque potentiel pour la santé des patients",
        ];
        
        // Pick one correct answer
        $correctAnswer = $correctAnswers[array_rand($correctAnswers)];
        
        // Pick 3 incorrect answers
        shuffle($incorrectAnswers);
        $incorrectOptions = array_slice($incorrectAnswers, 0, 3);
        
        // Combine all options
        $allOptions = array_merge([$correctAnswer], $incorrectOptions);
        
        // Shuffle to randomize position of correct answer
        shuffle($allOptions);
        
        return [
            'options' => $allOptions,
            'correct' => $correctAnswer
        ];
    }

    private function genererExplication(string $template, string $motCle): string
    {
        $explications = [
            "Cette réponse est correcte car elle reflète les principes fondamentaux de la pratique médicale moderne et est soutenue par des preuves scientifiques.",
            "Cette option est la bonne réponse car elle correspond aux recommandations des autorités de santé et aux protocoles cliniques établis.",
            "C'est la réponse appropriée car elle s'aligne avec les connaissances médicales actuelles et les meilleures pratiques cliniques.",
            "Cette réponse est exacte car elle représente l'approche standard validée par la recherche médicale et l'expérience clinique.",
        ];
        
        return $explications[array_rand($explications)];
    }

    private function construirePrompt(CoursEducatif $cours, int $nombre, string $difficulte): string
    {
        $difficulteText = match($difficulte) {
            'facile' => 'niveau débutant, questions simples',
            'moyen' => 'niveau intermédiaire, questions modérées',
            'difficile' => 'niveau avancé, questions complexes',
            default => 'niveau intermédiaire'
        };

        return <<<PROMPT
Tu es un expert médical spécialisé dans la création de quiz éducatifs pour des professionnels de santé.

Génère {$nombre} questions à choix multiples (QCM) basées sur le cours suivant:

TITRE: {$cours->getTitre()}
CONTENU: {$cours->getContenu()}

INSTRUCTIONS:
- Crée des questions de {$difficulteText}
- Chaque question doit avoir exactement 4 options de réponse (A, B, C, D)
- Une seule réponse correcte par question
- Fournis une explication médicale pour chaque réponse correcte
- Les questions doivent être pertinentes et basées sur le contenu du cours
- Utilise un langage médical approprié

FORMAT DE RÉPONSE (JSON):
[
  {
    "question": "Texte de la question",
    "options": ["Option A", "Option B", "Option C", "Option D"],
    "reponse_correcte": "Option A",
    "explication": "Explication médicale détaillée"
  }
]

Réponds UNIQUEMENT avec le JSON, sans texte supplémentaire.
PROMPT;
    }

    private function appellerAPI(string $prompt): string
    {
        $url = $this->apiUrl . '?key=' . $this->apiKey;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 3000,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('API Error: ' . $response);
        }

        $decoded = json_decode($response, true);
        
        if (!isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid API response format');
        }

        return $decoded['candidates'][0]['content']['parts'][0]['text'];
    }

    private function parserReponse(string $response, CoursEducatif $cours): array
    {
        // Extract JSON from response (in case there's extra text)
        $jsonStart = strpos($response, '[');
        $jsonEnd = strrpos($response, ']');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('Could not find JSON in AI response');
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $questionsData = json_decode($jsonString, true);

        if (!is_array($questionsData)) {
            throw new \Exception('Invalid JSON format in AI response');
        }

        $questions = [];
        foreach ($questionsData as $qData) {
            $question = new QuestionQuiz();
            $question->setCoursEducatif($cours);
            $question->setEnonce($qData['question']);
            $question->setOptionsReponsesArray($qData['options']);
            $question->setReponseCorrecte($qData['reponse_correcte']);
            $question->setExplication($qData['explication'] ?? '');
            $question->setStatut(StatutQuestion::IA_PROPOSE);
            
            $questions[] = $question;
        }

        return $questions;
    }
}
