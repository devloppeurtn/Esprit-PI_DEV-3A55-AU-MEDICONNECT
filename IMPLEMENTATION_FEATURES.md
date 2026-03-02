# MediConnect - Nouvelles Fonctionnalités Implémentées

## 📋 Résumé
Deux nouvelles fonctionnalités ont été ajoutées à MediConnect:

1. **Limite de participants pour les événements** - Les organisateurs peuvent fixer un nombre maximal de participants pour leurs événements
2. **Système d'évaluation des médecins** - Les patients peuvent évaluer les médecins avec une note (1-5 étoiles) et laisser des commentaires

---

## 1. Limite de Participants aux Événements

### Entités Modifiées

#### [src/Entity/Evenement.php](src/Entity/Evenement.php)
- ✅ Ajout du champ `maxParticipants` (INT, nullable)
- ✅ Ajout des méthodes getter/setter:
  - `getMaxParticipants(): ?int`
  - `setMaxParticipants(?int $maxParticipants): self`

### Validations Implémentées

#### [src/Controller/EvenementController.php](src/Controller/EvenementController.php)
- ✅ **Méthode `participer()`** - Validation avant d'ajouter un participant
  - Vérification que le nombre de participants n'a pas dépassé la limite
  - Message d'erreur: "Désolé, le nombre maximal de participants (X) a été atteint."

- ✅ **Méthode `participerAjax()`** - Validation AJAX pour les requêtes API
  - Même logique de vérification pour les appels AJAX
  - Retour d'erreur JSON si la limite est atteinte

### Formulaires

#### [src/Form/EvenementFormType.php](src/Form/EvenementFormType.php)
- ✅ Ajout du champ `maxParticipants` (type: IntegerType)
- Label: "Nombre maximal de participants (optionnel)"
- Placeholder: "Ex: 50"
- Validation: min=1

### Migrations Bases de Données

#### [migrations/Version20260224140000.php](migrations/Version20260224140000.php)
```sql
ALTER TABLE evenement ADD max_participants INT DEFAULT NULL;
```

#### Utilisation
- L'organisateur définit la limite lors de la création/modification d'un événement
- Le système refuse les inscriptions au-delà du limite définie
- Si aucune limite n'est définie (NULL), le nombre de participants est illimité

---

## 2. Système d'Évaluation des Médecins

### Nouvelle Entité

#### [src/Entity/AvisMedecin.php](src/Entity/AvisMedecin.php)
```php
class AvisMedecin {
    id: int (PRIMARY KEY, auto-increment)
    medecin: Medecin (FOREIGN KEY, cascade on delete)
    patient: Patient (FOREIGN KEY, cascade on delete)
    note: int (1-5 étoiles)
    commentaire: string (nullable, texte long jusqu'à 1000 caractères)
    dateCreation: DateTime (immutable)
}
```

**Contrainte UNIQUE**: Un patient ne peut laisser qu'un seul avis par médecin (peut être modifié)

### Entités Modifiées

#### [src/Entity/Medecin.php](src/Entity/Medecin.php)
- ✅ Ajout de la relation `OneToMany` vers `AvisMedecin`
- ✅ Initialisation dans le constructeur: `$this->avisMedecin = new ArrayCollection()`
- ✅ Méthodes collection:
  - `getAvisMedecin(): Collection`
  - `addAvisMedecin(AvisMedecin $avis): static`
  - `removeAvisMedecin(AvisMedecin $avis): static`

### Repository

#### [src/Repository/AvisMedecinRepository.php](src/Repository/AvisMedecinRepository.php)
Repository standard Doctrine pour les opérations CRUD sur les avis

### Formulaires

#### [src/Form/AvisMedecinFormType.php](src/Form/AvisMedecinFormType.php)
```php
Champs:
- note: ChoiceType (1-5 étoiles avec radio buttons)
  Options: 
    - "⭐ 1 étoile" => 1
    - "⭐⭐ 2 étoiles" => 2
    - "⭐⭐⭐ 3 étoiles" => 3
    - "⭐⭐⭐⭐ 4 étoiles" => 4
    - "⭐⭐⭐⭐⭐ 5 étoiles" => 5
  
- commentaire: TextareaType (optionnel)
  Placeholder: "Partagez votre expérience avec ce médecin..."
  Maxlength: 1000 caractères
```

### Contrôleur Patient

#### [src/Controller/PatientController.php](src/Controller/PatientController.php)

**Route 1: Évaluer un médecin**
```php
#[Route('/avis-medecin/{medecinId}', name: 'app_patient_rate_doctor', methods: ['GET', 'POST'])]
public function rateMedecin(Request $request, string $medecinId): Response
```
- Affiche le formulaire d'évaluation
- Gère la création d'un nouvel avis ou la modification d'un existant
- Authentification: Réservé aux patients (ROLE_PATIENT)

**Route 2: Consulter mes avis**
```php
#[Route('/mes-avis', name: 'app_patient_my_ratings', methods: ['GET'])]
public function myRatings(): Response
```
- Affiche tous les avis laissés par le patient
- Triés par date de création (le plus récent en premier)
- Authentification: Réservé aux patients (ROLE_PATIENT)

### Templates

#### [templates/patient/rate_medecin.html.twig](templates/patient/rate_medecin.html.twig)
- Formulaire pour évaluer un médecin
- Affichage du nom et spécialité du médecin
- Sélection visuelle des étoiles (1-5)
- Zone de texte pour les commentaires
- Distinction entre création et modification d'avis
- Styling responsive

#### [templates/patient/my_ratings.html.twig](templates/patient/my_ratings.html.twig)
- Affichage de tous les avis du patient
- Grille responsive (4 colonnes)
- Affiche pour chaque avis:
  - Nom et spécialité du médecin
  - Note en étoiles (visuellement représentée)
  - Commentaire (si présent)
  - Date de création
  - Bouton pour modifier l'avis
  - Bouton pour voir le profil du médecin
- Message vide si aucun avis

### Mise à Jour du Dashboard Patient

#### [templates/patient/index.html.twig](templates/patient/index.html.twig)
- ✅ Ajout d'une nouvelle carte "Mes avis" dans le dashboard
- Icône: star-fill (jaune)
- Lien vers [app_patient_my_ratings](templates/patient/my_ratings.html.twig)

### Migrations Bases de Données

#### [migrations/Version20260224150000.php](migrations/Version20260224150000.php)
```sql
CREATE TABLE avis_medecin (
    id INT AUTO_INCREMENT NOT NULL, 
    medecin_id VARCHAR(36) NOT NULL, 
    patient_id VARCHAR(36) NOT NULL, 
    note SMALLINT NOT NULL, 
    commentaire LONGTEXT DEFAULT NULL, 
    date_creation DATETIME NOT NULL, 
    UNIQUE INDEX uniq_avis_medecin_patient (medecin_id, patient_id), 
    INDEX IDX_4C5E4C4A4F31C15 (medecin_id), 
    INDEX IDX_4C5E4C4A6B899279 (patient_id), 
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

ALTER TABLE avis_medecin ADD CONSTRAINT FK_4C5E4C4A4F31C15 
    FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE;
ALTER TABLE avis_medecin ADD CONSTRAINT FK_4C5E4C4A6B899279 
    FOREIGN KEY (patient_id) REFERENCES utilisateur (id) ON DELETE CASCADE;
```

---

## 📦 Fichiers Créés/Modifiés

### ✅ Créés
1. `src/Entity/AvisMedecin.php`
2. `src/Repository/AvisMedecinRepository.php`
3. `src/Form/AvisMedecinFormType.php`
4. `templates/patient/rate_medecin.html.twig`
5. `templates/patient/my_ratings.html.twig`
6. `migrations/Version20260224140000.php`
7. `migrations/Version20260224150000.php`

### ✏️ Modifiés
1. `src/Entity/Evenement.php` - Ajout du champ maxParticipants
2. `src/Entity/Medecin.php` - Ajout de la relation OneToMany vers AvisMedecin
3. `src/Controller/EvenementController.php` - Validation de la limite de participants
4. `src/Controller/PatientController.php` - Ajout des actions d'évaluation
5. `src/Form/EvenementFormType.php` - Ajout du champ maxParticipants
6. `templates/patient/index.html.twig` - Ajout du lien vers les avis

---

## 🚀 Utilisation

### Pour les Organisateurs (Limite de Participants)
1. Créer ou modifier un événement
2. Remplir le champ "Nombre maximal de participants" (optionnel)
3. Sauvegarder l'événement
4. Le système refusera automatiquement les inscriptions au-delà de la limite

### Pour les Patients (Évaluation des Médecins)
1. Accéder au dashboard patient
2. Cliquer sur "Mes avis" ou sur un médecin depuis la liste
3. Remplir le formulaire d'évaluation:
   - Sélectionner une note (1-5 étoiles, obligatoire)
   - Écrire un commentaire (optionnel)
4. Soumettre le formulaire
5. Consulter les avis via "Mes avis"
6. Modifier un avis en cliquant sur le bouton "Modifier"

---

## 🔒 Sécurité
- ✅ Authentification ROLE_PATIENT pour les routes d'évaluation
- ✅ Contrainte UNIQUE pour éviter les doublons (un patient = un avis par médecin)
- ✅ Validations Doctrine sur les champs obligatoires
- ✅ Cascade ON DELETE pour maintenir l'intégrité des données

---

## 📝 Notes d'Exécution des Migrations
Exécuter les migrations Doctrine:
```bash
php bin/console doctrine:migrations:migrate
```

Cela créera:
1. La colonne `max_participants` dans la table `evenement`
2. La nouvelle table `avis_medecin` avec les relations appropriées
