# Savoir Médical Bundle

## Description
Bundle Symfony pour la gestion du module Savoir Médical de MediConnect.

## Fonctionnalités
- Gestion des catégories de santé (Culture Générale et Pathologies)
- Création et gestion de cours éducatifs
- **Génération de quiz avec IA (Groq API)**
- Système de validation des questions par les médecins
- Système d'approbation des catégories par les administrateurs
- **Système de notifications en temps réel (Mercure)**
- Suivi de progression des patients
- Attribution de badges

## Système de Notifications en Temps Réel

### Description
Pour améliorer la communication, la réactivité et l'engagement des utilisateurs, un système de notifications en temps réel a été implémenté pour les trois rôles principaux: administrateur, médecin et patient.

### Workflow des Notifications

1. **Médecin soumet une catégorie**
   - L'administrateur reçoit automatiquement une notification l'informant de la demande d'approbation en attente

2. **Admin approuve la catégorie**
   - Le médecin correspondant reçoit une notification de confirmation
   - Tous les patients reçoivent une notification les alertant de la disponibilité du nouveau contenu éducatif

3. **Admin rejette la catégorie**
   - Le médecin reçoit une notification de rejet avec invitation à réviser

### Technologies
- **Mercure Hub**: Push notifications en temps réel via Server-Sent Events
- **Doctrine ORM**: Persistance des notifications
- **JavaScript EventSource**: Réception côté client

### Types de Notifications
- `CATEGORIE_EN_ATTENTE`: Nouvelle catégorie à valider (→ Admin)
- `CATEGORIE_APPROUVEE`: Catégorie approuvée (→ Médecin)
- `CATEGORIE_REJETEE`: Catégorie rejetée (→ Médecin)
- `NOUVELLE_CATEGORIE`: Nouveau contenu disponible (→ Patients)

### Intégration
```twig
{# Dans votre template de navigation #}
{% include 'notifications/_notification_bell.html.twig' %}
```

### Documentation complète
Voir [NOTIFICATION_SYSTEM.md](NOTIFICATION_SYSTEM.md) pour la documentation technique détaillée.

## Intégration IA - Groq API

### Description
Notre plateforme intègre un système de génération de quiz basé sur l'IA utilisant l'API Groq. L'IA analyse le contenu des cours saisis dans chaque catégorie et génère dynamiquement des questions de quiz pertinentes avec leurs réponses correctes.

### Fonctionnement
1. **Analyse du contenu**: L'IA analyse le contenu éducatif du cours
2. **Génération dynamique**: Chaque clic sur le bouton "Générer" produit une nouvelle question
3. **Variété garantie**: Questions différentes basées sur le même cours
4. **Validation médicale**: Les questions générées doivent être validées par un médecin avant publication
5. **Statuts des questions**:
   - `IA_PROPOSE`: Question générée par l'IA, en attente de validation
   - `VALIDE_MEDECIN`: Question validée et disponible pour les patients

### Configuration API
- **Provider**: Groq AI
- **Model**: mixtral-8x7b-32768
- **API Key**: Configurée dans `groq_ai.yaml`
- **Endpoint**: https://api.groq.com/openai/v1/chat/completions

### Service GroqAIService
Le service `GroqAIService` gère toute l'intégration avec l'API Groq:

```php
// Générer une question
$question = $groqAIService->generateQuizQuestion($coursContent, $categorieName);

// Générer plusieurs questions
$questions = $groqAIService->generateMultipleQuestions($coursContent, $categorieName, 5);
```

### Format des questions générées
```php
[
    'question' => 'Question générée par l\'IA',
    'options' => [
        'A' => 'Option A',
        'B' => 'Option B',
        'C' => 'Option C',
        'D' => 'Option D',
    ],
    'correct_answer' => 'A',
    'explanation' => 'Explication de la réponse',
]
```

### Workflow de validation
1. Médecin crée un cours éducatif
2. Médecin clique sur "Générer question IA"
3. L'IA génère une question (statut: IA_PROPOSE)
4. Médecin ou Admin valide la question
5. Question devient disponible pour les patients (statut: VALIDE_MEDECIN)

### Avantages
- **Pédagogique**: Questions adaptées au contenu du cours
- **Variété**: Génération illimitée de questions différentes
- **Qualité**: Validation médicale obligatoire
- **Rapidité**: Génération instantanée
- **Pertinence**: Basé sur le contenu réel du cours

## Structure
```
SavoirMedicalBundle/
├── Controller/          # Contrôleurs du module
├── Entity/             # Entités Doctrine
├── Service/            # Services métier
│   └── GroqAIService.php  # Service d'intégration Groq AI
├── DependencyInjection/ # Configuration du bundle
└── Resources/
    ├── config/         # Fichiers de configuration
    │   ├── services.yaml
    │   ├── routes.yaml
    │   └── groq_ai.yaml  # Configuration Groq API
    └── views/          # Templates Twig
```

## Entités
- **CategorieSante**: Catégories de santé avec système d'approbation
- **CoursEducatif**: Cours éducatifs liés aux catégories
- **Question**: Questions de quiz avec validation médecin et génération IA
- **ReponsePatient**: Réponses des patients aux quiz
- **ProgressionUtilisateur**: Suivi de la progression

## Rôles et Permissions
- **ROLE_PATIENT**: Accès aux cours et quiz validés, suivi de progression
- **ROLE_MEDECIN**: Création de catégories/cours, génération et validation de questions IA
- **ROLE_ADMIN**: Approbation des catégories, validation de questions, accès complet

## Installation
Le bundle est automatiquement chargé via `config/bundles.php`

## Configuration
Les routes sont préfixées par `/savoir-medical`

## Sécurité
- API Key stockée dans la configuration (à déplacer vers .env en production)
- Rate limiting pour éviter les abus
- Validation médicale obligatoire avant publication
- Système d'approbation à plusieurs niveaux

## Technologies
- Symfony 6.x
- Doctrine ORM
- Groq AI API (Mixtral-8x7b)
- Twig Templates
- Bootstrap 5

