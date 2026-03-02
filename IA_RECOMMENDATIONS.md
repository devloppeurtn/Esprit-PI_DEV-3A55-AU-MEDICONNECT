# 🤖 Système de Recommandations IA pour les Événements

## 🎯 Vue d'ensemble

Le système de recommandations IA analyse automatiquement le dossier médical de chaque patient pour suggérer des événements pertinents. Il utilise une analyse intelligente des mots-clés médicaux et un système de scoring pour identifier les événements les plus adaptés à chaque patient.

## ✨ Fonctionnalités

### 1. Analyse du Profil Médical

Le système extrait et analyse les informations suivantes du dossier médical du patient :

- **Maladies chroniques** : Conditions médicales à long terme
- **Allergies** : Allergies connues du patient
- **Consultations récentes** : Diagnostics des 6 derniers mois
- **Médicaments actuels** : Traitements en cours

### 2. Correspondance Intelligente

Le système utilise un dictionnaire de mots-clés médicaux organisé en 10 catégories :

1. **Diabète** : diabète, glycémie, insuline, sucre, glucose
2. **Hypertension** : hypertension, tension, pression artérielle, cardiovasculaire
3. **Asthme** : asthme, respiratoire, poumon, bronches
4. **Allergie** : allergie, allergique, réaction
5. **Cardiaque** : cardiaque, cœur, cardiovasculaire, cardiologie
6. **Cancer** : cancer, oncologie, tumeur, chimiothérapie
7. **Obésité** : obésité, poids, surpoids, nutrition
8. **Dépression** : dépression, anxiété, mental, psychologique
9. **Arthrite** : arthrite, rhumatisme, articulation, douleur
10. **Thyroïde** : thyroïde, hormonal, endocrinologie

### 3. Système de Scoring

Chaque événement reçoit un score basé sur :

- **+10 points** : Correspondance directe avec une condition du patient
- **+3 points** : Correspondance partielle avec les mots du diagnostic
- **+2 points** : Événements de prévention/sensibilisation/dépistage

### 4. Affichage des Recommandations

Les 3 meilleurs événements sont affichés avec :
- Le titre de l'événement
- La date
- Le score de pertinence
- Les raisons de la recommandation
- Un lien direct vers les détails

## 🚀 Test Rapide

### Créer un Patient de Test

```bash
php bin/console app:create-test-patient
```

Cette commande crée automatiquement :
- **Email** : patient.test@mediconnect.com
- **Mot de passe** : test123
- **Profil** : Patient avec diabète et hypertension
- **Dossier médical** : Maladies chroniques et consultations récentes

### Tester les Recommandations

1. Connectez-vous avec le compte de test
2. Allez sur la page des événements : http://127.0.0.1:8000/evenement
3. Les recommandations IA apparaissent en haut de la page dans un encadré violet/bleu

### Créer des Événements de Test

Pour voir les recommandations en action, créez des événements avec ces titres :

```bash
# Événement pour diabétiques
"Atelier Gestion du Diabète et Nutrition"

# Événement pour hypertension
"Conférence Prévention Cardiovasculaire"

# Événement général
"Journée Mondiale de la Santé"
```

Le patient de test devrait voir les 2 premiers événements recommandés avec des scores élevés!

## 🏗️ Architecture Technique

### Service Principal

**Fichier** : `src/Service/EventRecommendationService.php`

**Méthodes principales** :

```php
// Obtenir les événements suggérés pour un patient
public function getSuggestedEvents(Patient $patient, int $limit = 5): array

// Extraire le profil médical du patient
private function extractMedicalProfile(Patient $patient): array

// Calculer le score de pertinence
private function calculateRelevanceScore(Evenement $event, array $medicalProfile): int

// Générer les raisons de la recommandation
private function getMatchReasons(Evenement $event, array $medicalProfile): array
```

### Intégration dans le Contrôleur

**Fichier** : `src/Controller/EvenementController.php`

Le service est injecté dans le constructeur et utilisé dans la méthode `index()` :

```php
// Recommandations IA pour les patients
$aiRecommendations = [];
if ($this->isGranted('ROLE_PATIENT')) {
    $user = $this->getUser();
    if ($user instanceof \App\Entity\Patient) {
        $suggestions = $this->recommendationService->getSuggestedEvents($user, 3);
        $aiRecommendations = $suggestions;
    }
}
```

### Template Twig

**Fichier** : `templates/evenement/index.html.twig`

Une section dédiée affiche les recommandations avec un design moderne en dégradé violet/bleu.

## 📖 Utilisation

### Pour les Patients

1. Connectez-vous avec votre compte patient
2. Accédez à la page des événements
3. Les recommandations IA apparaissent en haut de la page
4. Cliquez sur "Voir les détails" pour plus d'informations

### Pour les Développeurs

#### Ajouter de Nouveaux Mots-clés

Modifiez la constante `MEDICAL_KEYWORDS` dans `EventRecommendationService.php` :

```php
private const MEDICAL_KEYWORDS = [
    'nouvelle_categorie' => ['mot1', 'mot2', 'synonyme1'],
    // ...
];
```

#### Ajuster le Système de Scoring

Modifiez les valeurs dans la méthode `calculateRelevanceScore()` :

```php
$score += 10; // Correspondance directe
$score += 3;  // Correspondance partielle
$score += 2;  // Bonus prévention
```

#### Changer le Nombre de Recommandations

Dans `EvenementController.php`, modifiez le paramètre `limit` :

```php
$suggestions = $this->recommendationService->getSuggestedEvents($user, 5); // Au lieu de 3
```

## 💡 Exemples de Cas d'Usage

### Exemple 1 : Patient Diabétique

**Profil médical** :
- Maladies chroniques : "Diabète de type 2"
- Consultations récentes : "Contrôle glycémie"

**Événements recommandés** :
- "Atelier Gestion du Diabète" (Score: 20)
- "Conférence sur la Nutrition et le Diabète" (Score: 15)
- "Dépistage Gratuit du Diabète" (Score: 12)

### Exemple 2 : Patient Cardiaque

**Profil médical** :
- Maladies chroniques : "Insuffisance cardiaque"
- Allergies : "Aucune"

**Événements recommandés** :
- "Journée Mondiale du Cœur" (Score: 20)
- "Prévention des Maladies Cardiovasculaires" (Score: 12)
- "Atelier Activité Physique Adaptée" (Score: 5)

## 🧪 Tests

### Test Manuel Rapide

```bash
# 1. Créer un patient de test
php bin/console app:create-test-patient

# 2. Démarrer le serveur (si pas déjà fait)
symfony server:start --no-tls

# 3. Se connecter sur http://127.0.0.1:8000/login
# Email: patient.test@mediconnect.com
# Mot de passe: test123

# 4. Aller sur http://127.0.0.1:8000/evenement
# Les recommandations IA apparaissent en haut!
```

### Vérifier les Données du Patient

```bash
php bin/console doctrine:query:sql "SELECT u.email, dm.maladies_chroniques, dm.allergies FROM utilisateur u JOIN dossier_medical dm ON u.id = dm.patient_id WHERE u.email = 'patient.test@mediconnect.com'"
```

## 🔧 Dépannage

### Les recommandations n'apparaissent pas

1. **Vérifier que vous êtes connecté en tant que patient**

2. **Vérifier que le patient a un dossier médical**
   ```bash
   php bin/console doctrine:query:sql "SELECT * FROM dossier_medical WHERE patient_id = [ID_PATIENT]"
   ```

3. **Vérifier qu'il existe des événements validés**
   ```bash
   php bin/console doctrine:query:sql "SELECT * FROM evenement WHERE statut = 'VALIDE' AND is_active = 1"
   ```

4. **Vider le cache**
   ```bash
   php bin/console cache:clear
   ```

5. **Vérifier les logs**
   ```bash
   tail -f var/log/dev.log
   ```

## 📄 Fichiers Modifiés

- ✅ `src/Service/EventRecommendationService.php` (NOUVEAU)
- ✅ `src/Controller/EvenementController.php` (MODIFIÉ)
- ✅ `templates/evenement/index.html.twig` (MODIFIÉ)
- ✅ `src/Command/CreateTestPatientCommand.php` (NOUVEAU)
- ✅ `IA_RECOMMENDATIONS.md` (NOUVEAU)

## 🎉 Statut

✅ **SYSTÈME OPÉRATIONNEL** - Prêt à l'emploi!

Le système de recommandations IA est maintenant actif et fonctionnel. Un patient de test a été créé avec un dossier médical complet pour faciliter les tests.
