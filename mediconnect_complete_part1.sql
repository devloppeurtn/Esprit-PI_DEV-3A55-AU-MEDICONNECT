-- ============================================================================
-- MEDICONNECT - BASE DE DONNÉES COMPLÈTE FUSIONNÉE
-- ============================================================================
-- Version: Complète (Fusion des deux schémas)
-- Date: 07 Mai 2026
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Suppression et création de la base de données
DROP DATABASE IF EXISTS mediconnect;
CREATE DATABASE mediconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mediconnect;

-- ============================================================================
-- TABLE: utilisateur (Table principale - FUSIONNÉE)
-- ============================================================================
CREATE TABLE utilisateur (
    id INT AUTO_INCREMENT NOT NULL,
    email VARCHAR(180) NOT NULL,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    nom_complet VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    statut VARCHAR(255) NOT NULL,
    date_creation DATETIME NOT NULL,
    derniere_connexion DATETIME DEFAULT NULL,
    
    -- Champs communs
    telephone VARCHAR(20) DEFAULT NULL,
    date_naissance DATE DEFAULT NULL,
    adresse LONGTEXT DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    
    -- Authentification et sécurité
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    verification_token VARCHAR(255) DEFAULT NULL,
    verification_token_expires_at DATETIME DEFAULT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Authentification avancée
    biometric_enabled TINYINT(1) NOT NULL DEFAULT 0,
    face_embedding JSON DEFAULT NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    google_authenticator_secret VARCHAR(64) DEFAULT NULL,
    totp_secret VARCHAR(64) DEFAULT NULL,
    totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Champs spécifiques Médecin
    specialite VARCHAR(255) DEFAULT NULL,
    adresse_cabinet LONGTEXT DEFAULT NULL,
    numero_licence VARCHAR(100) DEFAULT NULL,
    
    -- Champs spécifiques Secrétaire
    medecin_id INT DEFAULT NULL,
    
    -- Champs spécifiques Organisateur
    role_dans_evenement VARCHAR(255) DEFAULT NULL,
    presence_confirmee TINYINT(1) DEFAULT NULL,
    
    -- Préférences notifications
    notif_commande_enabled TINYINT(1) NOT NULL DEFAULT 1,
    notif_promo_enabled TINYINT(1) NOT NULL DEFAULT 1,
    
    UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email),
    INDEX IDX_1D1C63B34F31A84 (medecin_id),
    INDEX idx_email (email),
    INDEX idx_role (role),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: dossier_medical
-- ============================================================================
CREATE TABLE dossier_medical (
    id INT AUTO_INCREMENT NOT NULL,
    date_creation DATETIME NOT NULL,
    allergies LONGTEXT DEFAULT NULL,
    maladies_chroniques LONGTEXT DEFAULT NULL,
    patient_id INT NOT NULL,
    UNIQUE INDEX UNIQ_3581EE626B899279 (patient_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: rendez_vous
-- ============================================================================
CREATE TABLE rendez_vous (
    id INT AUTO_INCREMENT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    statut VARCHAR(255) NOT NULL,
    note LONGTEXT DEFAULT NULL,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    google_calendar_event_id VARCHAR(255) DEFAULT NULL,
    INDEX IDX_65E8AA0A6B899279 (patient_id),
    INDEX IDX_65E8AA0A4F31A84 (medecin_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: consultation
-- ============================================================================
CREATE TABLE consultation (
    id INT AUTO_INCREMENT NOT NULL,
    date DATETIME NOT NULL,
    diagnostic LONGTEXT DEFAULT NULL,
    resume LONGTEXT DEFAULT NULL,
    dossier_medical_id INT NOT NULL,
    rendez_vous_id INT DEFAULT NULL,
    medecin_id INT NOT NULL,
    INDEX IDX_964685A67750B79F (dossier_medical_id),
    UNIQUE INDEX UNIQ_964685A691EF7EAA (rendez_vous_id),
    INDEX IDX_964685A64F31A84 (medecin_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: ordonnance
-- ============================================================================
CREATE TABLE ordonnance (
    id INT AUTO_INCREMENT NOT NULL,
    date_creation DATETIME NOT NULL,
    contenu_prescription LONGTEXT DEFAULT NULL,
    instructions LONGTEXT DEFAULT NULL,
    medicament VARCHAR(255) DEFAULT NULL,
    methode_utilisation LONGTEXT DEFAULT NULL,
    consultation_id INT NOT NULL,
    INDEX IDX_924B326C62FF6CDF (consultation_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: rapport_medical
-- ============================================================================
CREATE TABLE rapport_medical (
    id INT AUTO_INCREMENT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    contenu LONGTEXT DEFAULT NULL,
    date_creation DATETIME NOT NULL,
    consultation_id INT NOT NULL,
    INDEX IDX_C0B673962FF6CDF (consultation_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: medicament_actuel
-- ============================================================================
CREATE TABLE medicament_actuel (
    id INT AUTO_INCREMENT NOT NULL,
    medicament VARCHAR(255) NOT NULL,
    methode_utilisation LONGTEXT DEFAULT NULL,
    date_ajout DATETIME NOT NULL,
    dossier_medical_id INT NOT NULL,
    ordonnance_id INT DEFAULT NULL,
    INDEX IDX_33D025A47750B79F (dossier_medical_id),
    INDEX IDX_33D025A42BF23B8F (ordonnance_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: document_patient
-- ============================================================================
CREATE TABLE document_patient (
    id INT AUTO_INCREMENT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(255) NOT NULL,
    type_document VARCHAR(100) DEFAULT NULL,
    description LONGTEXT DEFAULT NULL,
    date_ajout DATETIME NOT NULL,
    ajoute_par_patient TINYINT(1) NOT NULL DEFAULT 1,
    dossier_medical_id INT NOT NULL,
    INDEX IDX_BEB571377750B79F (dossier_medical_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: planning_medecin
-- ============================================================================
CREATE TABLE planning_medecin (
    id INT AUTO_INCREMENT NOT NULL,
    heure_debut_matin TIME DEFAULT NULL,
    heure_fin_matin TIME DEFAULT NULL,
    heure_debut_apres_midi TIME DEFAULT NULL,
    heure_fin_apres_midi TIME DEFAULT NULL,
    duree_consultation INT NOT NULL DEFAULT 30,
    jours_ouverture JSON NOT NULL,
    medecin_id INT NOT NULL,
    UNIQUE INDEX UNIQ_C6B70E2D4F31A84 (medecin_id),
    UNIQUE INDEX UNIQ_planning_medecin_id (medecin_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: invitation
-- ============================================================================
CREATE TABLE invitation (
    id INT AUTO_INCREMENT NOT NULL,
    statut VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    date_creation DATETIME NOT NULL,
    date_reponse DATETIME DEFAULT NULL,
    medecin_id INT NOT NULL,
    secretaire_id INT NOT NULL,
    UNIQUE INDEX UNIQ_F11D61A25F37A13B (token),
    INDEX IDX_F11D61A24F31A84 (medecin_id),
    INDEX IDX_F11D61A2A90F02B2 (secretaire_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: notification
-- ============================================================================
CREATE TABLE notification (
    id INT AUTO_INCREMENT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message LONGTEXT NOT NULL,
    type VARCHAR(20) NOT NULL,
    lien VARCHAR(255) DEFAULT NULL,
    est_lu TINYINT(1) NOT NULL DEFAULT 0,
    lue TINYINT(1) DEFAULT 0,
    date_creation DATETIME NOT NULL,
    date_lecture DATETIME DEFAULT NULL,
    categorie_id CHAR(36) DEFAULT NULL,
    utilisateur_id INT NOT NULL,
    INDEX IDX_BF5476CAFB88E14F (utilisateur_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: categorie_sante
-- ============================================================================
CREATE TABLE categorie_sante (
    id BINARY(16) NOT NULL,
    nom VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    type VARCHAR(100) NOT NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'EN_ATTENTE',
    statut_approbation VARCHAR(30) DEFAULT 'EN_ATTENTE',
    date_approbation DATETIME DEFAULT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    cree_par_id INT DEFAULT NULL,
    approuve_par_id INT DEFAULT NULL,
    medecin_id INT DEFAULT NULL,
    admin_id BINARY(16) DEFAULT NULL,
    admin_nom VARCHAR(255) DEFAULT NULL,
    commentaire_admin TEXT DEFAULT NULL,
    INDEX IDX_3A70DFBBFC29C013 (cree_par_id),
    INDEX IDX_3A70DFBB5ED9CBB3 (approuve_par_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: cours_educatif
-- ============================================================================
CREATE TABLE cours_educatif (
    id BINARY(16) NOT NULL,
    titre VARCHAR(255) NOT NULL,
    contenu LONGTEXT NOT NULL,
    score_pour_badge INT NOT NULL,
    date_creation DATETIME NOT NULL,
    categorie_sante_id BINARY(16) NOT NULL,
    medecin_validateur_id INT DEFAULT NULL,
    medecin_id INT DEFAULT NULL,
    categorie_id BINARY(16) DEFAULT NULL,
    media_url VARCHAR(1000) DEFAULT NULL,
    media_type VARCHAR(32) DEFAULT NULL,
    media_public_id VARCHAR(255) DEFAULT NULL,
    media_original_name VARCHAR(255) DEFAULT NULL,
    media_resource_type VARCHAR(32) DEFAULT NULL,
    statut_approbation VARCHAR(30) DEFAULT 'EN_ATTENTE',
    admin_id BINARY(16) DEFAULT NULL,
    admin_nom VARCHAR(255) DEFAULT NULL,
    date_approbation DATETIME DEFAULT NULL,
    commentaire_admin TEXT DEFAULT NULL,
    INDEX IDX_B5621BF9EF7C17 (categorie_sante_id),
    INDEX IDX_B5621BFF3E3919 (medecin_validateur_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: question_quiz
-- ============================================================================
CREATE TABLE question_quiz (
    id BINARY(16) NOT NULL,
    enonce VARCHAR(500) NOT NULL,
    options_reponses LONGTEXT NOT NULL,
    reponse_correcte VARCHAR(255) NOT NULL,
    explication LONGTEXT DEFAULT NULL,
    statut VARCHAR(50) NOT NULL,
    date_creation DATETIME NOT NULL,
    cours_educatif_id BINARY(16) NOT NULL,
    medecin_validateur_id INT DEFAULT NULL,
    cours_id BINARY(16) DEFAULT NULL,
    medecin_id INT DEFAULT NULL,
    quiz_id VARCHAR(100) DEFAULT NULL,
    statut_approbation VARCHAR(30) DEFAULT 'EN_ATTENTE',
    admin_id BINARY(16) DEFAULT NULL,
    admin_nom VARCHAR(255) DEFAULT NULL,
    date_approbation DATETIME DEFAULT NULL,
    commentaire_admin TEXT DEFAULT NULL,
    INDEX IDX_FAFC177D9AF2C787 (cours_educatif_id),
    INDEX IDX_FAFC177DFF3E3919 (medecin_validateur_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: progression_utilisateur
-- ============================================================================
CREATE TABLE progression_utilisateur (
    id BINARY(16) NOT NULL,
    score_max INT NOT NULL,
    nb_tentatives INT NOT NULL,
    badge_nom VARCHAR(255) DEFAULT NULL,
    date_obtention DATETIME DEFAULT NULL,
    est_complete TINYINT(1) NOT NULL,
    utilisateur_id INT NOT NULL,
    categorie_sante_id BINARY(16) NOT NULL,
    INDEX IDX_BC58001FFB88E14F (utilisateur_id),
    INDEX IDX_BC58001FF9EF7C17 (categorie_sante_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: reponse_utilisateur
-- ============================================================================
CREATE TABLE reponse_utilisateur (
    id BINARY(16) NOT NULL,
    reponse_choisie VARCHAR(255) NOT NULL,
    est_correcte TINYINT(1) NOT NULL,
    points_obtenus INT NOT NULL,
    date_reponse DATETIME NOT NULL,
    utilisateur_id INT NOT NULL,
    question_id BINARY(16) NOT NULL,
    INDEX IDX_14B756B6FB88E14F (utilisateur_id),
    INDEX IDX_14B756B61E27F6BF (question_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: patient_progress
-- ============================================================================
CREATE TABLE patient_progress (
    id BINARY(16) NOT NULL,
    patient_id VARCHAR(50) NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    total_points INT DEFAULT 0,
    courses_completed INT DEFAULT 0,
    quizzes_completed INT DEFAULT 0,
    last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    has_discount TINYINT(1) DEFAULT 0,
    discount_earned_date TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX patient_id (patient_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: quiz_attempts
-- ============================================================================
CREATE TABLE quiz_attempts (
    id BINARY(16) NOT NULL,
    patient_id VARCHAR(50) NOT NULL,
    cours_id VARCHAR(50) NOT NULL,
    cours_title VARCHAR(255) NOT NULL,
    total_questions INT NOT NULL,
    correct_answers INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    date_completed TIMESTAMP NULL DEFAULT NULL,
    completed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patient_id (patient_id),
    INDEX idx_cours_id (cours_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

