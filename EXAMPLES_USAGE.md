# 📚 Exemples d'Utilisation - Nouvelles Fonctionnalités

## 🎯 Cas d'Usage - Limite de Participants

### Scénario: Créer un séminaire avec limite de places

#### Étape 1: Organisateur crée l'événement
```
URL: /evenement/nouveau
Formulaire:
├── Titre: "Séminaire de Formation - Dermatologie"
├── Contenu: "Formation pratique sur les dernières techniques..."
├── Date: 15/03/2026
├── Heure: 14:00
├── Lieu: "Auditorium Principal - Hôpital Central"
├── 🆕 Nombre maximal de participants: 50
└── Actif: ✓

Résultat: Événement créé avec limite de 50 participants max
```

#### Étape 2: Les patients s'inscrivent
```
Patient 1: S'inscrit → Nombre de participants: 1/50 ✅
Patient 2: S'inscrit → Nombre de participants: 2/50 ✅
...
Patient 50: S'inscrit → Nombre de participants: 50/50 ✅
Patient 51: Essaie de s'inscrire → ❌ ERREUR
Message: "Désolé, le nombre maximal de participants (50) a été atteint."
```

#### Étape 3: Organisateur consulte les statistiques
```
URL: /evenement/statistiques
Affichage:
├── Mes événements: 3
├── En attente: 1
├── Validés: 2
├── Refusés: 0
└── Total participants: 50
```

---

## ⭐ Cas d'Usage - Système d'Évaluation

### Scénario 1: Patient évalue un médecin après une consultation

#### Étape 1: Patient accède à son espace
```
URL: /patient/
Dashboard affiche:
├── Mon dossier médical
├── Nos médecins
├── Mes rendez-vous
└── 🆕 Mes avis ← Nouveau!
```

#### Étape 2: Patient clique sur "Mes avis"
```
URL: /patient/mes-avis

S'il n'a pas d'avis:
┌─────────────────────────────────┐
│  Vous n'avez pas encore         │
│  évalué de médecin              │
│                                 │
│  [Consulter nos médecins]       │
└─────────────────────────────────┘

S'il en a:
Carte 1:
├── Dr. Jean Dupont - Cardiologue
├── ⭐⭐⭐⭐⭐ (5 étoiles)
├── Commentaire: "Excellent médecin, très attentif et à l'écoute."
├── Date: 24 février 2026
└── [Modifier] [Voir profil]

Carte 2:
├── Dr. Marie Dubois - Dermatologue
├── ⭐⭐⭐⭐ (4 étoiles)
├── Commentaire: "Très compétente, consultation un peu longue."
├── Date: 20 février 2026
└── [Modifier] [Voir profil]
```

#### Étape 3: Patient évalue un nouveau médecin
```
URL: /patient/avis-medecin/UUID-du-medecin
GET request → Affiche le formulaire

Formulaire:
┌────────────────────────────────────────┐
│ Évaluer le Docteur                     │
│ Dr. Sophie Martin - Pédiatre           │
├────────────────────────────────────────┤
│ Note (Étoiles) *                       │
│ ○ ⭐ 1 étoile                          │
│ ○ ⭐⭐ 2 étoiles                       │
│ ○ ⭐⭐⭐ 3 étoiles                     │
│ ● ⭐⭐⭐⭐⭐ 5 étoiles (sélectionné)  │
│                                        │
│ Commentaire (Optionnel)                │
│ ┌────────────────────────────────────┐ │
│ │ Docteur très disponible, à l'écoute │ │
│ │ des parents, explique bien les      │ │
│ │ traitements. Strongly recommended! │ │
│ └────────────────────────────────────┘ │
│ Caractères: 98/1000                    │
│                                        │
│ [Soumettre mon avis] [Annuler]        │
└────────────────────────────────────────┘

POST request → Enregistre l'avis
Redirection → /patient/mes-avis
Flash message: "Merci pour votre avis !"
```

### Scénario 2: Patient modifie son évaluation

#### Situation:
Patient a déjà évalué Dr. Dupont avec 3 étoiles, mais veut changer à 5 étoiles.

#### Processus:
```
URL: /patient/mes-avis
Clique sur [Modifier] de l'avis

URL: /patient/avis-medecin/UUID-du-medecin
GET request → Formulaire pré-rempli:
├── Note: ⭐⭐⭐ (sélectionné)
└── Commentaire: "Moyen, pas très à l'écoute"

Patient met à jour:
├── Note: ⭐⭐⭐⭐⭐
└── Commentaire: "Finalement, très bon médecin!"

POST request → Mise à jour
Redirection → /patient/mes-avis
Flash message: "Votre avis a été mis à jour"
```

---

## 🔧 Exemples de Code

### Exemple 1: Créer une évaluation (en code)

```php
// Dans un contrôleur ou service
$medecin = $medecinRepository->find($medecinId);
$patient = $this->getUser(); // Patient authentifié

// Créer un nouvel avis
$avis = new AvisMedecin();
$avis->setMedecin($medecin);
$avis->setPatient($patient);
$avis->setNote(5); // 1-5 étoiles
$avis->setCommentaire("Excellent médecin!");

$em->persist($avis);
$em->flush();
```

### Exemple 2: Récupérer les avis d'un patient

```php
// Service ou Repository
$patient = $this->getUser();
$avis = $avisRepository->findBy(
    ['patient' => $patient],
    ['dateCreation' => 'DESC']  // Triés par date décroissante
);

foreach ($avis as $review) {
    echo $review->getMedecin()->getNomComplet(); // Dr. XXX
    echo $review->getNote(); // 1-5
    echo $review->getCommentaire(); // Texte
    echo $review->getDateCreation()->format('d/m/Y'); // Date
}
```

### Exemple 3: Vérifier la limite de participants

```php
// Dans EvenementController
$evenement = $evenementRepository->find($id);
$currentCount = count($participantRepository->findByEvenement($evenement));

if ($evenement->getMaxParticipants() !== null) {
    if ($currentCount >= $evenement->getMaxParticipants()) {
        // Limite atteinte
        throw new \Exception("Limite de participants atteinte");
    }
}

// Ajouter le participant
$participant = new Participant();
// ... configuration ...
$em->persist($participant);
$em->flush();
```

---

## 📊 Requêtes SQL Utiles

### Voir tous les avis d'un médecin

```sql
SELECT 
    p.nom_complet,
    am.note,
    am.commentaire,
    DATE_FORMAT(am.date_creation, '%d/%m/%Y') as date
FROM avis_medecin am
JOIN utilisateur p ON am.patient_id = p.id
WHERE am.medecin_id = 'UUID-DU-MEDECIN'
ORDER BY am.date_creation DESC;
```

**Résultat:**
```
nom_complet          | note | commentaire                  | date
---------------------|------|-----------------------------|-----------
Jean Durand          |  5   | Excellent!                  | 24/02/2026
Marie Leblanc        |  4   | Très bon, un peu pressé     | 20/02/2026
Pierre Martin        |  5   | Recommandé!                 | 15/02/2026
```

### Voir la moyenne des notes d'un médecin

```sql
SELECT 
    m.nom_complet,
    m.specialite,
    COUNT(am.id) as nombre_avis,
    ROUND(AVG(am.note), 2) as moyenne_note,
    MIN(am.note) as note_min,
    MAX(am.note) as note_max
FROM avis_medecin am
JOIN utilisateur m ON am.medecin_id = m.id
WHERE m.type_entity = 'medecin'
GROUP BY am.medecin_id
ORDER BY moyenne_note DESC;
```

**Résultat:**
```
nom_complet       | specialite    | nombre_avis | moyenne_note | note_min | note_max
------------------|---------------|-------------|--------------|----------|----------
Dr. Jean Dupont   | Cardiologue   | 15          | 4.73         | 4        | 5
Dr. Sophie Martin | Pédiatre      | 8           | 4.50         | 4        | 5
Dr. Marie Dubois  | Dermatologue  | 12          | 4.25         | 3        | 5
```

### Voir les événements avec limite atteinte

```sql
SELECT 
    e.title,
    e.max_participants,
    COUNT(p.id) as current_participants,
    ROUND((COUNT(p.id) * 100.0 / e.max_participants), 1) as pourcentage
FROM evenement e
LEFT JOIN participant p ON e.id = p.evenement_id
WHERE e.max_participants IS NOT NULL
GROUP BY e.id
HAVING COUNT(p.id) >= e.max_participants
ORDER BY pourcentage DESC;
```

**Résultat:**
```
title                              | max_participants | current_participants | pourcentage
------------------------------------|------------------|---------------------|------------
Séminaire Dermatologie             | 50               | 50                   | 100.0
Formation Cardiologie              | 30               | 29                   | 96.7
Conférence Pédiatrie              | 40               | 35                   | 87.5
```

---

## 🎨 Interface Mobile vs Desktop

### Desktop (1200px+)
```
Grille d'avis: 4 colonnes
┌─────────────┬─────────────┬─────────────┬─────────────┐
│   Avis 1    │   Avis 2    │   Avis 3    │   Avis 4    │
└─────────────┴─────────────┴─────────────┴─────────────┘
```

### Tablet (768px-1199px)
```
Grille d'avis: 2 colonnes
┌─────────────┬─────────────┐
│   Avis 1    │   Avis 2    │
├─────────────┼─────────────┤
│   Avis 3    │   Avis 4    │
└─────────────┴─────────────┘
```

### Mobile (<768px)
```
Grille d'avis: 1 colonne
┌─────────────┐
│   Avis 1    │
├─────────────┤
│   Avis 2    │
├─────────────┤
│   Avis 3    │
└─────────────┘
```

---

## ❌ Gestion des Erreurs

### Erreur 1: Limite de participants atteinte
```
Request: POST /evenement/{id}/participer
Status: 302 (redirect)
Flash: "Désolé, le nombre maximal de participants (50) a été atteint."
Redirect: /evenement/{id}
```

### Erreur 2: Double évaluation
```
Request: POST /patient/avis-medecin/{medecinId}
Situation: Patient a déjà un avis pour ce médecin
Résultat: Formulaire pré-rempli pour modification
Pas d'erreur, mais mise à jour de l'avis existant
```

### Erreur 3: Patient non authentifié
```
Request: GET /patient/avis-medecin/{medecinId}
Status: 302 (redirect)
Redirect: /login
Flash: "Vous devez être connecté pour accéder à cette page"
```

---

## 🔄 Flux de Données

### Flux 1: Limite de Participants
```
formulaire.html → POST → Controller.participer()
                            ↓
                    Récupère événement
                            ↓
                    Vérifie limite vs. count(participants)
                            ↓
                    ├─ Limite atteinte → Flash error + Redirect
                    │
                    └─ OK → Crée Participant + Flush
                            ↓
                    Flash success + Redirect
```

### Flux 2: Évaluation
```
rate_medecin.html → POST → Controller.rateMedecin()
                              ↓
                    Cherche AvisMedecin existant?
                              ↓
                    ├─ Existe → Mise à jour
                    │
                    └─ N'existe pas → Création
                                        ↓
                                  Valide + Flush
                                        ↓
                                  Flash success + Redirect
```

---

## 📞 Support & FAQ

**Q: Je veux limiter un événement existant?**
A: Accédez à l'édition de l'événement et remplissez le champ "Nombre maximal de participants".

**Q: Je peux évaluer un médecin plusieurs fois?**
A: Non, une seule évaluation par médecin. Vous pouvez la modifier via le bouton "Modifier".

**Q: Où voir la moyenne des notes d'un médecin?**
A: Cette fonctionnalité peut être ajoutée sur la page du médecin. Voir requête SQL ci-dessus.

**Q: Les avis sont-ils visibles publiquement?**
A: Actuellement non, seulement pour le patient. Peut être étendu.

**Q: Je veux supprimer mon avis?**
A: Fonctionnalité non implémentée actuellement. Contactez l'admin.

