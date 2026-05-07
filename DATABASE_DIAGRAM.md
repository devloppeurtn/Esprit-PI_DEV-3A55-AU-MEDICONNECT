# MediConnect - Diagramme de Base de Données

## 🗂️ Vue d'Ensemble des Tables

```
┌─────────────────────────────────────────────────────────────────┐
│                    MEDICONNECT DATABASE                          │
│                     27 Tables - 35+ Relations                    │
└─────────────────────────────────────────────────────────────────┘
```

## 📊 Modules Principaux

### 🔐 Module Authentification & Utilisateurs

```
┌──────────────────────────────────────────────────────────────────┐
│                         UTILISATEUR                               │
├──────────────────────────────────────────────────────────────────┤
│ • id (PK)                    • google_id                         │
│ • email (UNIQUE)             • photo                             │
│ • mot_de_passe_hash          • face_embedding (JSON)             │
│ • nom_complet                • google_authenticator_secret       │
│ • role                       • discr (discriminator)             │
│ • statut                     • telephone                         │
│ • date_creation              • date_naissance                    │
│ • derniere_connexion         • adresse                           │
│ • reset_token                • specialite (Médecin)              │
│ • reset_token_expires_at     • adresse_cabinet (Médecin)         │
│ • verification_token         • numero_licence (Médecin)          │
│ • verification_token_expires • medecin_id (FK - Secrétaire)      │
│ • email_verified             • role_dans_evenement (Organisateur)│
│ • biometric_enabled          • presence_confirmee (Organisateur) │
└──────────────────────────────────────────────────────────────────┘
                    │
                    ├─── Patient (discr = 'patient')
                    ├─── Medecin (discr = 'medecin')
                    ├─── Secretaire (discr = 'secretaire')
                    ├─── Admin (discr = 'admin')
                    └─── Organisateur (discr = 'organisateur')

┌──────────────────────────────┐
│   WEBAUTHN_CREDENTIAL        │
├──────────────────────────────┤
│ • id (PK)                    │
│ • public_key_credential_id   │
│ • type                       │
│ • transports (JSON)          │
│ • attestation_type           │
│ • trust_path (JSON)          │
│ • aaguid                     │
│ • credential_public_key      │
│ • user_handle                │
│ • counter                    │
│ • other_ui (JSON)            │
└──────────────────────────────┘
```

### 🏥 Module Médical

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          FLUX MÉDICAL                                    │
└─────────────────────────────────────────────────────────────────────────┘

    PATIENT                RENDEZ_VOUS              CONSULTATION
┌──────────────┐        ┌──────────────┐        ┌──────────────────┐
│ utilisateur  │───────>│ • id (PK)    │───────>│ • id (PK)        │
│ (Patient)    │        │ • date_debut │        │ • date           │
└──────────────┘        │ • date_fin   │        │ • diagnostic     │
                        │ • statut     │        │ • resume         │
    MEDECIN             │ • note       │        │ • dossier_id(FK) │
┌──────────────┐        │ • patient_id │        │ • rdv_id (FK)    │
│ utilisateur  │───────>│ • medecin_id │        │ • medecin_id(FK) │
│ (Medecin)    │        └──────────────┘        └──────────────────┘
└──────────────┘                                         │
                                                         ├──────────────┐
                                                         │              │
                                                         ▼              ▼
                                              ┌──────────────┐  ┌──────────────┐
                                              │ ORDONNANCE   │  │RAPPORT_MEDICAL│
                                              ├──────────────┤  ├──────────────┤
                                              │ • id (PK)    │  │ • id (PK)    │
                                              │ • date       │  │ • titre      │
                                              │ • contenu    │  │ • contenu    │
                                              │ • medicament │  │ • date       │
                                              │ • consult_id │  │ • consult_id │
                                              └──────────────┘  └──────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│                      DOSSIER_MEDICAL                                    │
├────────────────────────────────────────────────────────────────────────┤
│ • id (PK)                                                              │
│ • date_creation                                                        │
│ • allergies                                                            │
│ • maladies_chroniques                                                  │
│ • patient_id (FK) → utilisateur                                        │
└────────────────────────────────────────────────────────────────────────┘
                    │
                    ├──────────────────────────────────┐
                    │                                  │
                    ▼                                  ▼
    ┌──────────────────────────┐      ┌──────────────────────────┐
    │  MEDICAMENT_ACTUEL       │      │  DOCUMENT_PATIENT        │
    ├──────────────────────────┤      ├──────────────────────────┤
    │ • id (PK)                │      │ • id (PK)                │
    │ • medicament             │      │ • nom_fichier            │
    │ • methode_utilisation    │      │ • chemin_fichier         │
    │ • date_ajout             │      │ • type_document          │
    │ • dossier_medical_id(FK) │      │ • description            │
    │ • ordonnance_id (FK)     │      │ • date_ajout             │
    └──────────────────────────┘      │ • ajoute_par_patient     │
                                      │ • dossier_medical_id(FK) │
                                      └──────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│                      PLANNING_MEDECIN                                   │
├────────────────────────────────────────────────────────────────────────┤
│ • id (PK)                                                              │
│ • heure_debut_matin                                                    │
│ • heure_fin_matin                                                      │
│ • heure_debut_apres_midi                                               │
│ • heure_fin_apres_midi                                                 │
│ • duree_consultation                                                   │
│ • jours_ouverture (JSON)                                               │
│ • medecin_id (FK) → utilisateur                                        │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│                         INVITATION                                      │
├────────────────────────────────────────────────────────────────────────┤
│ • id (PK)                                                              │
│ • statut                                                               │
│ • token (UNIQUE)                                                       │
│ • date_creation                                                        │
│ • date_reponse                                                         │
│ • medecin_id (FK) → utilisateur                                        │
│ • secretaire_id (FK) → utilisateur                                     │
└────────────────────────────────────────────────────────────────────────┘
```

### 📚 Module Éducatif

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      SYSTÈME ÉDUCATIF                                    │
└─────────────────────────────────────────────────────────────────────────┘

    ┌──────────────────────────┐
    │   CATEGORIE_SANTE        │
    ├──────────────────────────┤
    │ • id (PK - UUID)         │
    │ • nom                    │
    │ • description            │
    │ • type                   │
    │ • statut                 │
    │ • date_approbation       │
    │ • cree_par_id (FK)       │
    │ • approuve_par_id (FK)   │
    └──────────────────────────┘
                │
                ├────────────────────────────────────┐
                │                                    │
                ▼                                    ▼
    ┌──────────────────────────┐      ┌──────────────────────────┐
    │   COURS_EDUCATIF         │      │ PROGRESSION_UTILISATEUR  │
    ├──────────────────────────┤      ├──────────────────────────┤
    │ • id (PK - UUID)         │      │ • id (PK - UUID)         │
    │ • titre                  │      │ • score_max              │
    │ • contenu                │      │ • nb_tentatives          │
    │ • score_pour_badge       │      │ • badge_nom              │
    │ • date_creation          │      │ • date_obtention         │
    │ • categorie_sante_id(FK) │      │ • est_complete           │
    │ • medecin_validateur(FK) │      │ • utilisateur_id (FK)    │
    └──────────────────────────┘      │ • categorie_sante_id(FK) │
                │                     └──────────────────────────┘
                │
                ▼
    ┌──────────────────────────┐
    │   QUESTION_QUIZ          │
    ├──────────────────────────┤
    │ • id (PK - UUID)         │
    │ • enonce                 │
    │ • options_reponses       │
    │ • reponse_correcte       │
    │ • explication            │
    │ • statut                 │
    │ • date_creation          │
    │ • cours_educatif_id (FK) │
    │ • medecin_validateur(FK) │
    └──────────────────────────┘
                │
                │
                ▼
    ┌──────────────────────────┐
    │  REPONSE_UTILISATEUR     │
    ├──────────────────────────┤
    │ • id (PK - UUID)         │
    │ • reponse_choisie        │
    │ • est_correcte           │
    │ • points_obtenus         │
    │ • date_reponse           │
    │ • utilisateur_id (FK)    │
    │ • question_id (FK)       │
    └──────────────────────────┘
```

### 🛒 Module E-Commerce

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      SYSTÈME E-COMMERCE                                  │
└─────────────────────────────────────────────────────────────────────────┘

    ┌──────────────────────────┐
    │  CATEGORIE_PRODUIT       │
    ├──────────────────────────┤
    │ • id (PK)                │
    │ • nom                    │
    │ • description            │
    │ • image                  │
    └──────────────────────────┘
                │
                │
                ▼
    ┌──────────────────────────┐
    │       PRODUIT            │
    ├──────────────────────────┤
    │ • id (PK)                │
    │ • nom                    │
    │ • description            │
    │ • prix                   │
    │ • stock                  │
    │ • image                  │
    │ • categorie_id (FK)      │
    └──────────────────────────┘
                │
                ├────────────────────────────────────┐
                │                                    │
                ▼                                    ▼
    ┌──────────────────────────┐      ┌──────────────────────────┐
    │   LIGNE_COMMANDE         │      │     AVIS_PRODUIT         │
    ├──────────────────────────┤      ├──────────────────────────┤
    │ • id (PK)                │      │ • id (PK)                │
    │ • quantite               │      │ • note                   │
    │ • prix_unitaire          │      │ • commentaire            │
    │ • commande_id (FK)       │      │ • date_creation          │
    │ • produit_id (FK)        │      │ • produit_id (FK)        │
    └──────────────────────────┘      │ • utilisateur_id (FK)    │
                │                     └──────────────────────────┘
                │
                ▼
    ┌──────────────────────────────────────────────────────────┐
    │              COMMANDE_PRODUIT                             │
    ├──────────────────────────────────────────────────────────┤
    │ • id (PK)                                                │
    │ • date_commande                                          │
    │ • statut                                                 │
    │ • montant_total                                          │
    │ • adresse_livraison                                      │
    │ • telephone                                              │
    │ • pays                                                   │
    │ • mode_paiement                                          │
    │ • delivery_city                                          │
    │ • delivery_carrier                                       │
    │ • delivery_traffic_level                                 │
    │ • delivery_cutoff_applied                                │
    │ • delivery_eta_at                                        │
    │ • delivery_committed_at                                  │
    │ • delivery_delay_penalty_points                          │
    │ • delivery_sla_breached                                  │
    │ • delivered_at                                           │
    │ • utilisateur_id (FK)                                    │
    └──────────────────────────────────────────────────────────┘

    ┌──────────────────────────┐
    │      PROMO_CODE          │
    ├──────────────────────────┤
    │ • id (PK)                │
    │ • code (UNIQUE)          │
    │ • rate                   │
    │ • start_at               │
    │ • end_at                 │
    │ • usage_limit            │
    │ • used_count             │
    │ • active                 │
    │ • created_at             │
    └──────────────────────────┘
```

### 📅 Module Événements

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      SYSTÈME ÉVÉNEMENTS                                  │
└─────────────────────────────────────────────────────────────────────────┘

    ┌──────────────────────────────────────────────────────────┐
    │                    EVENEMENT                              │
    ├──────────────────────────────────────────────────────────┤
    │ • id (PK - UUID)                                         │
    │ • title                                                  │
    │ • content                                                │
    │ • is_active                                              │
    │ • statut                                                 │
    │ • approuve_at                                            │
    │ • event_date                                             │
    │ • location                                               │
    │ • event_time                                             │
    │ • max_participants                                       │
    │ • type_evenement                                         │
    │ • attachment_path                                        │
    │ • attachment_original_name                               │
    │ • created_at                                             │
    │ • organisateur_id (FK) → utilisateur                     │
    │ • approuve_par_id (FK) → utilisateur                     │
    └──────────────────────────────────────────────────────────┘
                            │
                            │
                            ▼
    ┌──────────────────────────────────────────────────────────┐
    │                   PARTICIPANT                             │
    ├──────────────────────────────────────────────────────────┤
    │ • id (PK)                                                │
    │ • first_name                                             │
    │ • last_name                                              │
    │ • email                                                  │
    │ • created_at                                             │
    │ • evenement_id (FK) → evenement                          │
    └──────────────────────────────────────────────────────────┘
```

### 🔔 Module Notifications

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         NOTIFICATION                                     │
├─────────────────────────────────────────────────────────────────────────┤
│ • id (PK)                                                               │
│ • titre                                                                 │
│ • message                                                               │
│ • type                                                                  │
│ • lien                                                                  │
│ • est_lu                                                                │
│ • date_creation                                                         │
│ • date_lecture                                                          │
│ • categorie_id                                                          │
│ • utilisateur_id (FK) → utilisateur                                     │
└─────────────────────────────────────────────────────────────────────────┘
```

### ⚙️ Module Système

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      MESSENGER_MESSAGES                                  │
├─────────────────────────────────────────────────────────────────────────┤
│ • id (PK)                                                               │
│ • body                                                                  │
│ • headers                                                               │
│ • queue_name                                                            │
│ • created_at                                                            │
│ • available_at                                                          │
│ • delivered_at                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

## 🔗 Relations Clés

### Relations 1:1 (One-to-One)
- `utilisateur (Patient)` ←→ `dossier_medical`
- `utilisateur (Medecin)` ←→ `planning_medecin`
- `rendez_vous` ←→ `consultation`

### Relations 1:N (One-to-Many)
- `utilisateur (Medecin)` → `rendez_vous` (plusieurs)
- `utilisateur (Patient)` → `rendez_vous` (plusieurs)
- `dossier_medical` → `consultation` (plusieurs)
- `consultation` → `ordonnance` (plusieurs)
- `consultation` → `rapport_medical` (plusieurs)
- `categorie_produit` → `produit` (plusieurs)
- `utilisateur` → `commande_produit` (plusieurs)
- `commande_produit` → `ligne_commande` (plusieurs)
- `evenement` → `participant` (plusieurs)

### Relations N:M (Many-to-Many)
- `utilisateur` ←→ `cours_educatif` (via `progression_utilisateur`)
- `utilisateur` ←→ `question_quiz` (via `reponse_utilisateur`)
- `produit` ←→ `commande_produit` (via `ligne_commande`)

## 📈 Index et Performance

### Index Principaux
- **UNIQUE**: email, token, code promo
- **INDEX**: Toutes les clés étrangères
- **COMPOSITE**: (produit_id, utilisateur_id) pour avis_produit

### Optimisations
- Index sur les colonnes de recherche fréquente
- Index sur les colonnes de tri (date_creation, date_commande)
- Index composites pour les requêtes complexes

## 🎯 Bonnes Pratiques Implémentées

✅ Normalisation (3NF)
✅ Contraintes d'intégrité référentielle
✅ Cascade DELETE approprié
✅ Index sur clés étrangères
✅ Types de données appropriés
✅ Encodage UTF-8
✅ Timestamps pour audit
✅ Soft delete possible via statut
✅ Tokens avec expiration
✅ Support JSON pour données flexibles
