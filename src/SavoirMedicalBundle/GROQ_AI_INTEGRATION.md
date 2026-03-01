# Groq AI Integration - Documentation Technique

## Vue d'ensemble
Le module Savoir Médical intègre l'API Groq pour générer automatiquement des questions de quiz basées sur le contenu des cours éducatifs.

## Configuration API

### Credentials
- **API Provider**: Groq AI
- **API Key**: `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`
- **Endpoint**: `https://api.groq.com/openai/v1/chat/completions`
- **Model**: `mixtral-8x7b-32768`

### Paramètres du modèle
```yaml
model: mixtral-8x7b-32768
temperature: 0.7          # Contrôle la créativité (0.0 = déterministe, 1.0 = créatif)
max_tokens: 500           # Longueur maximale de la réponse
```

## Architecture

### Service: GroqAIService
Localisation: `src/SavoirMedicalBundle/Service/GroqAIService.php`

#### Méthodes principales

##### 1. generateQuizQuestion()
Génère une seule question de quiz.

```php
public function generateQuizQuestion(string $coursContent, string $categorieName): array
```

**Paramètres:**
- `$coursContent`: Le contenu complet du cours éducatif
- `$categorieName`: Le nom de la catégorie (pour le contexte)

**Retour:**
```php
[
    'question' => 'Quelle est la fonction principale du cœur ?',
    'options' => [
        'A' => 'Filtrer le sang',
        'B' => 'Pomper le sang dans tout le corps',
        'C' => 'Produire des globules rouges',
        'D' => 'Stocker l\'oxygène',
    ],
    'correct_answer' => 'B',
    'explanation' => 'Le cœur est un muscle qui pompe le sang...',
    'raw_content' => '...' // Réponse brute de l\'IA
]
```

##### 2. generateMultipleQuestions()
Génère plusieurs questions en une seule fois.

```php
public function generateMultipleQuestions(
    string $coursContent, 
    string $categorieName, 
    int $count = 5
): array
```

**Paramètres:**
- `$coursContent`: Le contenu du cours
- `$categorieName`: Le nom de la catégorie
- `$count`: Nombre de questions à générer (défaut: 5)

**Retour:** Tableau de questions au même format que `generateQuizQuestion()`

## Workflow de génération

### 1. Préparation du prompt
Le service construit un prompt structuré:

```
Basé sur le cours suivant de la catégorie "{categorie}":

{contenu du cours}

Génère UNE question de quiz pertinente avec:
1. Une question claire et précise
2. Quatre options de réponse (A, B, C, D)
3. La lettre de la bonne réponse
4. Une explication courte de la réponse

Format de réponse attendu:
QUESTION: [ta question]
A) [option A]
B) [option B]
C) [option C]
D) [option D]
REPONSE: [A, B, C ou D]
EXPLICATION: [explication courte]
```

### 2. Appel API
```php
POST https://api.groq.com/openai/v1/chat/completions
Headers:
  Authorization: Bearer {API_KEY}
  Content-Type: application/json

Body:
{
  "model": "mixtral-8x7b-32768",
  "messages": [
    {
      "role": "system",
      "content": "Tu es un expert médical qui crée des questions de quiz pédagogiques."
    },
    {
      "role": "user",
      "content": "{prompt}"
    }
  ],
  "temperature": 0.7,
  "max_tokens": 500
}
```

### 3. Parsing de la réponse
Le service utilise des expressions régulières pour extraire:
- La question
- Les 4 options (A, B, C, D)
- La réponse correcte
- L'explication

### 4. Validation et stockage
- Question créée avec statut `IA_PROPOSE`
- Médecin/Admin peut valider → statut `VALIDE_MEDECIN`
- Seules les questions validées apparaissent dans les quiz patients

## Intégration dans le contrôleur

### Exemple d'utilisation
```php
use App\SavoirMedicalBundle\Service\GroqAIService;

class SavoirMedicalController extends AbstractController
{
    public function __construct(
        private GroqAIService $groqAIService
    ) {}

    #[Route('/cours/{id}/generer-question-ia', name: 'app_generer_question_ia')]
    public function genererQuestionIA(CoursEducatif $cours): Response
    {
        try {
            // Générer la question
            $questionData = $this->groqAIService->generateQuizQuestion(
                $cours->getContenu(),
                $cours->getCategorie()->getNom()
            );
            
            // Créer l'entité Question
            $question = new Question();
            $question->setTexte($questionData['question']);
            $question->setReponseA($questionData['options']['A']);
            $question->setReponseB($questionData['options']['B']);
            $question->setReponseC($questionData['options']['C']);
            $question->setReponseD($questionData['options']['D']);
            $question->setBonneReponse($questionData['correct_answer']);
            $question->setStatut(StatutQuestion::IA_PROPOSE);
            $question->setCours($cours);
            
            // Sauvegarder
            $entityManager->persist($question);
            $entityManager->flush();
            
            return $this->json(['success' => true, 'question' => $questionData]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

## Gestion des erreurs

### Erreurs possibles
1. **API Key invalide**: Vérifier la clé dans la configuration
2. **Rate limiting**: Délai de 500ms entre les requêtes multiples
3. **Parsing échoué**: L'IA n'a pas respecté le format attendu
4. **Timeout**: Augmenter le timeout HTTP client
5. **Quota dépassé**: Vérifier les limites de l'API Groq

### Gestion dans le code
```php
try {
    $question = $this->groqAIService->generateQuizQuestion($content, $category);
} catch (\RuntimeException $e) {
    // Erreur de génération
    $this->addFlash('error', 'Impossible de générer la question: ' . $e->getMessage());
} catch (\Exception $e) {
    // Erreur générale
    $this->addFlash('error', 'Une erreur est survenue');
}
```

## Sécurité

### Bonnes pratiques
1. **API Key**: Déplacer vers `.env` en production
   ```env
   GROQ_API_KEY=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
   ```

2. **Rate Limiting**: Implémenter un système de limitation côté application

3. **Validation**: Toujours valider les questions générées avant publication

4. **Logging**: Logger les appels API pour le monitoring

5. **Cache**: Considérer le cache pour les questions fréquemment générées

## Performance

### Optimisations
- **Génération asynchrone**: Utiliser Symfony Messenger pour les générations multiples
- **Cache**: Mettre en cache les questions générées
- **Batch processing**: Générer plusieurs questions en arrière-plan

### Métriques
- Temps moyen de génération: ~2-3 secondes
- Coût par question: Variable selon le plan Groq
- Taux de succès: ~95% (avec parsing correct)

## Tests

### Test unitaire du service
```php
public function testGenerateQuizQuestion(): void
{
    $service = new GroqAIService($this->httpClient);
    
    $result = $service->generateQuizQuestion(
        'Le cœur est un organe vital...',
        'Anatomie'
    );
    
    $this->assertArrayHasKey('question', $result);
    $this->assertArrayHasKey('options', $result);
    $this->assertCount(4, $result['options']);
    $this->assertContains($result['correct_answer'], ['A', 'B', 'C', 'D']);
}
```

## Maintenance

### Monitoring
- Surveiller le taux d'erreur API
- Vérifier les quotas Groq
- Analyser la qualité des questions générées

### Mises à jour
- Tester les nouveaux modèles Groq
- Ajuster les prompts selon les retours utilisateurs
- Optimiser le parsing selon les changements de format

## Support
Pour toute question technique sur l'intégration Groq AI:
- Documentation Groq: https://console.groq.com/docs
- Support MediConnect: contact@mediconnect.fr
