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
    
    -- Discriminateur pour l'héritage Doctrine (IMPORTANT!)
    discr VARCHAR(255) NOT NULL DEFAULT 'patient',
    
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

-- ============================================================================
-- PARTIE 2: TABLES E-COMMERCE ET ÉVÉNEMENTS
-- ============================================================================

-- ============================================================================
-- TABLE: evenement
-- ============================================================================
CREATE TABLE evenement (
    id CHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL,
    statut VARCHAR(20) NOT NULL,
    approuve_at DATETIME DEFAULT NULL,
    event_date DATE DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    event_time VARCHAR(10) DEFAULT NULL,
    max_participants INT DEFAULT NULL,
    type_evenement VARCHAR(64) DEFAULT NULL,
    attachment_path VARCHAR(255) DEFAULT NULL,
    attachment_original_name VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    organisateur_id INT DEFAULT NULL,
    approuve_par_id INT DEFAULT NULL,
    INDEX IDX_B26681ED936B2FA (organisateur_id),
    INDEX IDX_B26681E5ED9CBB3 (approuve_par_id),
    INDEX IDX_B26681E2B80B8B0 (approuve_par_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: participant
-- ============================================================================
CREATE TABLE participant (
    id INT AUTO_INCREMENT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(180) NOT NULL,
    telephone VARCHAR(50) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    evenement_id CHAR(36) NOT NULL,
    ticket_sent TINYINT(1) DEFAULT 0,
    ticket_code VARCHAR(100) DEFAULT NULL,
    INDEX IDX_D79F6B11FD02F13 (evenement_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: categorie_produit
-- ============================================================================
CREATE TABLE categorie_produit (
    id INT AUTO_INCREMENT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description VARCHAR(500) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: produit
-- ============================================================================
CREATE TABLE produit (
    id INT AUTO_INCREMENT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description LONGTEXT DEFAULT NULL,
    prix DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    categorie_id INT NOT NULL,
    categorie VARCHAR(100) DEFAULT NULL,
    youtube_url VARCHAR(500) DEFAULT NULL,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX IDX_29A5EC27BCF5E72D (categorie_id),
    INDEX idx_categorie (categorie),
    INDEX idx_nom (nom),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: commande_produit
-- ============================================================================
CREATE TABLE commande_produit (
    id INT AUTO_INCREMENT NOT NULL,
    date_commande DATETIME NOT NULL,
    statut VARCHAR(255) NOT NULL,
    montant_total DECIMAL(10, 2) DEFAULT NULL,
    adresse_livraison LONGTEXT DEFAULT NULL,
    telephone VARCHAR(32) DEFAULT NULL,
    pays VARCHAR(64) DEFAULT NULL,
    mode_paiement VARCHAR(32) DEFAULT NULL,
    delivery_city VARCHAR(128) DEFAULT NULL,
    delivery_carrier VARCHAR(32) DEFAULT 'STANDARD' NOT NULL,
    delivery_traffic_level VARCHAR(16) DEFAULT 'MEDIUM' NOT NULL,
    delivery_cutoff_applied TINYINT(1) NOT NULL DEFAULT 0,
    delivery_eta_at DATETIME DEFAULT NULL,
    delivery_committed_at DATETIME DEFAULT NULL,
    delivery_delay_penalty_points INT NOT NULL DEFAULT 0,
    delivery_sla_breached TINYINT(1) NOT NULL DEFAULT 0,
    delivered_at DATETIME DEFAULT NULL,
    utilisateur_id INT NOT NULL,
    code_promo_id INT DEFAULT NULL,
    montant_reduction DECIMAL(10, 2) DEFAULT 0.00,
    montant_avant_reduction DECIMAL(10, 2) DEFAULT NULL,
    INDEX IDX_DF1E9E87FB88E14F (utilisateur_id),
    INDEX IDX_COMMANDE_DELIVERY_ETA (delivery_eta_at),
    INDEX IDX_COMMANDE_DELIVERY_SLA (delivery_sla_breached),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: ligne_commande
-- ============================================================================
CREATE TABLE ligne_commande (
    id INT AUTO_INCREMENT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10, 2) NOT NULL,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    INDEX IDX_3170B74B82EA2E54 (commande_id),
    INDEX IDX_3170B74BF347EFB (produit_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: avis_produit
-- ============================================================================
CREATE TABLE avis_produit (
    id INT AUTO_INCREMENT NOT NULL,
    note SMALLINT NOT NULL,
    commentaire LONGTEXT DEFAULT NULL,
    date_creation DATETIME NOT NULL,
    produit_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    INDEX IDX_2A67C21F347EFB (produit_id),
    INDEX IDX_2A67C21FB88E14F (utilisateur_id),
    INDEX IDX_5A9E2C19F347EFB (produit_id),
    INDEX IDX_5A9E2C1FB88E14F (utilisateur_id),
    UNIQUE INDEX uniq_avis_produit_user (produit_id, utilisateur_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: code_promo
-- ============================================================================
CREATE TABLE code_promo (
    id INT AUTO_INCREMENT NOT NULL,
    code VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    type_reduction VARCHAR(30) NOT NULL,
    valeur DECIMAL(10, 2) NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    utilisation_max INT DEFAULT NULL,
    utilisation_actuelle INT DEFAULT 0,
    montant_minimum DECIMAL(10, 2) DEFAULT 0.00,
    actif TINYINT(1) DEFAULT 1,
    date_creation DATETIME NOT NULL,
    UNIQUE INDEX code (code),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: promo_code
-- ============================================================================
CREATE TABLE promo_code (
    id INT AUTO_INCREMENT NOT NULL,
    code VARCHAR(50) NOT NULL,
    rate DECIMAL(5, 2) NOT NULL,
    start_at DATETIME DEFAULT NULL,
    end_at DATETIME DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    UNIQUE INDEX UNIQ_3D8C939E77153098 (code),
    UNIQUE INDEX UNIQ_PROMO_CODE_CODE (code),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: utilisation_code_promo
-- ============================================================================
CREATE TABLE utilisation_code_promo (
    id INT AUTO_INCREMENT NOT NULL,
    code_promo_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    commande_id INT NOT NULL,
    montant_reduction DECIMAL(10, 2) NOT NULL,
    date_utilisation DATETIME NOT NULL,
    INDEX code_promo_id (code_promo_id),
    INDEX utilisateur_id (utilisateur_id),
    INDEX commande_id (commande_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: panier
-- ============================================================================
CREATE TABLE panier (
    id INT AUTO_INCREMENT NOT NULL,
    utilisateur_id INT NOT NULL,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_utilisateur (utilisateur_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: panier_item
-- ============================================================================
CREATE TABLE panier_item (
    id INT AUTO_INCREMENT NOT NULL,
    panier_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    date_ajout TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX unique_panier_produit (panier_id, produit_id),
    INDEX panier_item_ibfk_2 (produit_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- PARTIE 3: TABLES CHAT, SYSTÈME ET CONTRAINTES
-- ============================================================================

-- ============================================================================
-- TABLE: chat_conversation
-- ============================================================================
CREATE TABLE chat_conversation (
    id INT AUTO_INCREMENT NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'DIRECT',
    direct_key VARCHAR(64) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE INDEX uk_chat_direct_key (direct_key),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: chat_message
-- ============================================================================
CREATE TABLE chat_message (
    id INT AUTO_INCREMENT NOT NULL,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    read_at DATETIME DEFAULT NULL,
    INDEX idx_chat_msg_conv_created (conversation_id, created_at),
    INDEX fk_chat_msg_sender (sender_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: chat_participant
-- ============================================================================
CREATE TABLE chat_participant (
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at DATETIME NOT NULL,
    INDEX idx_chat_participant_user (user_id),
    PRIMARY KEY (conversation_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: chat_mobile_session
-- ============================================================================
CREATE TABLE chat_mobile_session (
    token VARCHAR(100) NOT NULL,
    user_id INT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    device_label VARCHAR(120) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chat_mobile_session_expires (expires_at),
    INDEX fk_chat_mobile_session_user (user_id),
    PRIMARY KEY (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: chat_mobile_token
-- ============================================================================
CREATE TABLE chat_mobile_token (
    token VARCHAR(100) NOT NULL,
    user_id INT NOT NULL,
    conversation_id INT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_chat_mobile_expires (expires_at),
    INDEX fk_chat_mobile_user (user_id),
    INDEX fk_chat_mobile_conv (conversation_id),
    PRIMARY KEY (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: user_presence
-- ============================================================================
CREATE TABLE user_presence (
    user_id INT NOT NULL,
    last_seen_at DATETIME NOT NULL,
    PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: app_settings
-- ============================================================================
CREATE TABLE app_settings (
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: webauthn_credential
-- ============================================================================
CREATE TABLE webauthn_credential (
    id INT AUTO_INCREMENT NOT NULL,
    public_key_credential_id LONGTEXT NOT NULL,
    type VARCHAR(255) NOT NULL,
    transports JSON NOT NULL,
    attestation_type VARCHAR(255) NOT NULL,
    trust_path JSON NOT NULL,
    aaguid TINYTEXT NOT NULL,
    credential_public_key LONGTEXT NOT NULL,
    user_handle VARCHAR(255) NOT NULL,
    counter INT NOT NULL,
    other_ui JSON DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: messenger_messages
-- ============================================================================
CREATE TABLE messenger_messages (
    id BIGINT AUTO_INCREMENT NOT NULL,
    body LONGTEXT NOT NULL,
    headers LONGTEXT NOT NULL,
    queue_name VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL,
    available_at DATETIME NOT NULL,
    delivered_at DATETIME DEFAULT NULL,
    INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: doctrine_migration_versions
-- ============================================================================
CREATE TABLE doctrine_migration_versions (
    version VARCHAR(191) NOT NULL,
    executed_at DATETIME DEFAULT NULL,
    execution_time INT DEFAULT NULL,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CONTRAINTES DE CLÉS ÉTRANGÈRES
-- ============================================================================

-- Utilisateur
ALTER TABLE utilisateur 
    ADD CONSTRAINT FK_1D1C63B34F31A84 
    FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE SET NULL;

-- Dossier Médical
ALTER TABLE dossier_medical 
    ADD CONSTRAINT FK_3581EE626B899279 
    FOREIGN KEY (patient_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

-- Rendez-vous
ALTER TABLE rendez_vous 
    ADD CONSTRAINT FK_65E8AA0A6B899279 
    FOREIGN KEY (patient_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

ALTER TABLE rendez_vous 
    ADD CONSTRAINT FK_65E8AA0A4F31A84 
    FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

-- Consultation
ALTER TABLE consultation 
    ADD CONSTRAINT FK_964685A67750B79F 
    FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id) ON DELETE CASCADE;

ALTER TABLE consultation 
    ADD CONSTRAINT FK_964685A691EF7EAA 
    FOREIGN KEY (rendez_vous_id) REFERENCES rendez_vous (id) ON DELETE SET NULL;

ALTER TABLE consultation 
    ADD CONSTRAINT FK_964685A64F31A84 
    FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

-- Ordonnance
ALTER TABLE ordonnance 
    ADD CONSTRAINT FK_924B326C62FF6CDF 
    FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE;

-- Rapport Médical
ALTER TABLE rapport_medical 
    ADD CONSTRAINT FK_C0B673962FF6CDF 
    FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE;

-- Médicament Actuel
ALTER TABLE medicament_actuel 
    ADD CONSTRAINT FK_33D025A47750B79F 
    FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id) ON DELETE CASCADE;

ALTER TABLE medicament_actuel 
    ADD CONSTRAINT FK_33D025A42BF23B8F 
    FOREIGN KEY (ordonnance_id) REFERENCES ordonnance (id) ON DELETE SET NULL;

-- Document Patient
ALTER TABLE document_patient 
    ADD CONSTRAINT FK_BEB571377750B79F 
    FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id) ON DELETE CASCADE;

-- Planning Médecin
ALTER TABLE planning_medecin 
    ADD CONSTRAINT FK_C6B70E2D4F31A84 
    FOREIGN KEY (medecin_id) REFERENCES utilisateur (id);

ALTER TABLE planning_medecin 
    ADD CONSTRAINT FK_planning_medecin_medecin 
    FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

-- Invitation
ALTER TABLE invitation 
    ADD CONSTRAINT FK_F11D61A24F31A84 
    FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

ALTER TABLE invitation 
    ADD CONSTRAINT FK_F11D61A2A90F02B2 
    FOREIGN KEY (secretaire_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

-- Notification
ALTER TABLE notification 
    ADD CONSTRAINT FK_BF5476CAFB88E14F 
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id);

-- Cours Éducatif
ALTER TABLE cours_educatif 
    ADD CONSTRAINT FK_B5621BF9EF7C17 
    FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id);

ALTER TABLE cours_educatif 
    ADD CONSTRAINT FK_B5621BFF3E3919 
    FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id);

-- Question Quiz
ALTER TABLE question_quiz 
    ADD CONSTRAINT FK_FAFC177D9AF2C787 
    FOREIGN KEY (cours_educatif_id) REFERENCES cours_educatif (id);

ALTER TABLE question_quiz 
    ADD CONSTRAINT FK_FAFC177DFF3E3919 
    FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id);

-- Progression Utilisateur
ALTER TABLE progression_utilisateur 
    ADD CONSTRAINT FK_BC58001FFB88E14F 
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id);

ALTER TABLE progression_utilisateur 
    ADD CONSTRAINT FK_BC58001FF9EF7C17 
    FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id);

-- Réponse Utilisateur
ALTER TABLE reponse_utilisateur 
    ADD CONSTRAINT FK_14B756B6FB88E14F 
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id);

ALTER TABLE reponse_utilisateur 
    ADD CONSTRAINT FK_14B756B61E27F6BF 
    FOREIGN KEY (question_id) REFERENCES question_quiz (id);

-- Événement
ALTER TABLE evenement 
    ADD CONSTRAINT FK_B26681ED936B2FA 
    FOREIGN KEY (organisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL;

ALTER TABLE evenement 
    ADD CONSTRAINT FK_B26681E5ED9CBB3 
    FOREIGN KEY (approuve_par_id) REFERENCES utilisateur (id) ON DELETE SET NULL;

ALTER TABLE evenement 
    ADD CONSTRAINT FK_B26681E2B80B8B0 
    FOREIGN KEY (approuve_par_id) REFERENCES utilisateur (id) ON DELETE SET NULL;

-- Participant
ALTER TABLE participant 
    ADD CONSTRAINT FK_D79F6B11FD02F13 
    FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE;

-- Produit
ALTER TABLE produit 
    ADD CONSTRAINT FK_29A5EC27BCF5E72D 
    FOREIGN KEY (categorie_id) REFERENCES categorie_produit (id);

-- Commande Produit
ALTER TABLE commande_produit 
    ADD CONSTRAINT FK_DF1E9E87FB88E14F 
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id);

-- Ligne Commande
ALTER TABLE ligne_commande 
    ADD CONSTRAINT FK_3170B74B82EA2E54 
    FOREIGN KEY (commande_id) REFERENCES commande_produit (id);

ALTER TABLE ligne_commande 
    ADD CONSTRAINT FK_3170B74BF347EFB 
    FOREIGN KEY (produit_id) REFERENCES produit (id);

-- Avis Produit
ALTER TABLE avis_produit 
    ADD CONSTRAINT FK_2A67C21F347EFB 
    FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE;

ALTER TABLE avis_produit 
    ADD CONSTRAINT FK_2A67C21FB88E14F 
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

-- Panier
ALTER TABLE panier 
    ADD CONSTRAINT panier_ibfk_1 
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

-- Panier Item
ALTER TABLE panier_item 
    ADD CONSTRAINT panier_item_ibfk_1 
    FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE;

ALTER TABLE panier_item 
    ADD CONSTRAINT panier_item_ibfk_2 
    FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE;

-- Utilisation Code Promo
ALTER TABLE utilisation_code_promo 
    ADD CONSTRAINT utilisation_code_promo_ibfk_1 
    FOREIGN KEY (code_promo_id) REFERENCES code_promo (id) ON DELETE CASCADE;

ALTER TABLE utilisation_code_promo 
    ADD CONSTRAINT utilisation_code_promo_ibfk_2 
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

ALTER TABLE utilisation_code_promo 
    ADD CONSTRAINT utilisation_code_promo_ibfk_3 
    FOREIGN KEY (commande_id) REFERENCES commande_produit (id) ON DELETE CASCADE;

-- Chat
ALTER TABLE chat_message 
    ADD CONSTRAINT fk_chat_msg_conv 
    FOREIGN KEY (conversation_id) REFERENCES chat_conversation (id) ON DELETE CASCADE;

ALTER TABLE chat_message 
    ADD CONSTRAINT fk_chat_msg_sender 
    FOREIGN KEY (sender_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

ALTER TABLE chat_participant 
    ADD CONSTRAINT fk_chat_part_conv 
    FOREIGN KEY (conversation_id) REFERENCES chat_conversation (id) ON DELETE CASCADE;

ALTER TABLE chat_participant 
    ADD CONSTRAINT fk_chat_part_user 
    FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

ALTER TABLE chat_mobile_session 
    ADD CONSTRAINT fk_chat_mobile_session_user 
    FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

ALTER TABLE chat_mobile_token 
    ADD CONSTRAINT fk_chat_mobile_user 
    FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

ALTER TABLE chat_mobile_token 
    ADD CONSTRAINT fk_chat_mobile_conv 
    FOREIGN KEY (conversation_id) REFERENCES chat_conversation (id) ON DELETE CASCADE;

ALTER TABLE user_presence 
    ADD CONSTRAINT fk_presence_user 
    FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE;

COMMIT;

-- ============================================================================
-- FIN DU SCHÉMA COMPLET FUSIONNÉ
-- ============================================================================
-- Total Tables: 40+
-- Toutes les fonctionnalités incluses:
-- - Gestion médicale complète
-- - Système éducatif avec quiz
-- - E-commerce avec panier et codes promo
-- - Événements et participants
-- - Chat en temps réel
-- - Notifications
-- - Authentification avancée (2FA, biométrie, OAuth)
-- ============================================================================
