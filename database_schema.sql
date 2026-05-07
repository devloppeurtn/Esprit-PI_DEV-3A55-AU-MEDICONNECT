-- ============================================================================
-- MEDICONNECT - Schéma de Base de Données
-- ============================================================================
-- Projet: PIDEV – 3ème Année Ingénierie
-- Institution: Esprit School of Engineering
-- Année Académique: 2025–2026
-- ============================================================================

-- Suppression de la base de données si elle existe
DROP DATABASE IF EXISTS mediconnect;

-- Création de la base de données
CREATE DATABASE mediconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mediconnect;

-- ============================================================================
-- TABLE: utilisateur (Table principale des utilisateurs)
-- ============================================================================
-- Gère tous les types d'utilisateurs: Patient, Médecin, Secrétaire, Admin, Organisateur
CREATE TABLE utilisateur (
    id INT AUTO_INCREMENT NOT NULL,
    email VARCHAR(180) NOT NULL,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    nom_complet VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    statut VARCHAR(255) NOT NULL,
    date_creation DATETIME NOT NULL,
    derniere_connexion DATETIME DEFAULT NULL,
    
    -- Réinitialisation de mot de passe
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    
    -- Vérification d'email
    verification_token VARCHAR(100) DEFAULT NULL,
    verification_token_expires_at DATETIME DEFAULT NULL,
    email_verified TINYINT DEFAULT 0 NOT NULL,
    
    -- Authentification biométrique et OAuth
    biometric_enabled TINYINT DEFAULT 0 NOT NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    face_embedding JSON DEFAULT NULL,
    google_authenticator_secret VARCHAR(64) DEFAULT NULL,
    
    -- Discriminateur pour l'héritage (Patient, Medecin, Secretaire, Admin, Organisateur)
    discr VARCHAR(255) NOT NULL,
    
    -- Champs spécifiques Patient/Secrétaire
    telephone VARCHAR(20) DEFAULT NULL,
    date_naissance DATE DEFAULT NULL,
    adresse LONGTEXT DEFAULT NULL,
    
    -- Champs spécifiques Médecin
    specialite VARCHAR(255) DEFAULT NULL,
    adresse_cabinet LONGTEXT DEFAULT NULL,
    numero_licence VARCHAR(100) DEFAULT NULL,
    medecin_id INT DEFAULT NULL, -- Pour Secrétaire (référence au médecin)
    
    -- Champs spécifiques Organisateur
    role_dans_evenement VARCHAR(255) DEFAULT NULL,
    presence_confirmee TINYINT DEFAULT NULL,
    
    UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email),
    INDEX IDX_1D1C63B34F31A84 (medecin_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: dossier_medical
-- ============================================================================
-- Dossier médical d'un patient
CREATE TABLE dossier_medical (
    id INT AUTO_INCREMENT NOT NULL,
    date_creation DATETIME NOT NULL,
    allergies LONGTEXT DEFAULT NULL,
    maladies_chroniques LONGTEXT DEFAULT NULL,
    patient_id INT NOT NULL,
    UNIQUE INDEX UNIQ_3581EE626B899279 (patient_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: rendez_vous
-- ============================================================================
-- Rendez-vous entre patient et médecin
CREATE TABLE rendez_vous (
    id INT AUTO_INCREMENT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    statut VARCHAR(255) NOT NULL,
    note LONGTEXT DEFAULT NULL,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    INDEX IDX_65E8AA0A6B899279 (patient_id),
    INDEX IDX_65E8AA0A4F31A84 (medecin_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: consultation
-- ============================================================================
-- Consultation médicale suite à un rendez-vous
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
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: ordonnance
-- ============================================================================
-- Ordonnances médicales
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
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: rapport_medical
-- ============================================================================
-- Rapports médicaux générés lors des consultations
CREATE TABLE rapport_medical (
    id INT AUTO_INCREMENT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    contenu LONGTEXT DEFAULT NULL,
    date_creation DATETIME NOT NULL,
    consultation_id INT NOT NULL,
    INDEX IDX_C0B673962FF6CDF (consultation_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: medicament_actuel
-- ============================================================================
-- Médicaments actuels du patient
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
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: document_patient
-- ============================================================================
-- Documents médicaux uploadés (PDF, images, etc.)
CREATE TABLE document_patient (
    id INT AUTO_INCREMENT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(255) NOT NULL,
    type_document VARCHAR(100) DEFAULT NULL,
    description LONGTEXT DEFAULT NULL,
    date_ajout DATETIME NOT NULL,
    ajoute_par_patient TINYINT DEFAULT 1 NOT NULL,
    dossier_medical_id INT NOT NULL,
    INDEX IDX_BEB571377750B79F (dossier_medical_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: planning_medecin
-- ============================================================================
-- Planning de disponibilité des médecins
CREATE TABLE planning_medecin (
    id INT AUTO_INCREMENT NOT NULL,
    heure_debut_matin TIME DEFAULT NULL,
    heure_fin_matin TIME DEFAULT NULL,
    heure_debut_apres_midi TIME DEFAULT NULL,
    heure_fin_apres_midi TIME DEFAULT NULL,
    duree_consultation INT NOT NULL,
    jours_ouverture JSON NOT NULL,
    medecin_id INT NOT NULL,
    UNIQUE INDEX UNIQ_C6B70E2D4F31A84 (medecin_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: invitation
-- ============================================================================
-- Invitations envoyées par les médecins aux secrétaires
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
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: notification
-- ============================================================================
-- Notifications système pour les utilisateurs
CREATE TABLE notification (
    id INT AUTO_INCREMENT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message LONGTEXT NOT NULL,
    type VARCHAR(20) NOT NULL,
    lien VARCHAR(255) DEFAULT NULL,
    est_lu TINYINT NOT NULL,
    date_creation DATETIME NOT NULL,
    date_lecture DATETIME DEFAULT NULL,
    categorie_id BINARY(16) DEFAULT NULL,
    utilisateur_id INT NOT NULL,
    INDEX IDX_BF5476CAFB88E14F (utilisateur_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: categorie_sante
-- ============================================================================
-- Catégories de santé pour les cours éducatifs
CREATE TABLE categorie_sante (
    id BINARY(16) NOT NULL,
    nom VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    type VARCHAR(100) NOT NULL,
    statut VARCHAR(50) NOT NULL,
    date_approbation DATETIME DEFAULT NULL,
    cree_par_id INT DEFAULT NULL,
    approuve_par_id INT DEFAULT NULL,
    INDEX IDX_3A70DFBBFC29C013 (cree_par_id),
    INDEX IDX_3A70DFBB5ED9CBB3 (approuve_par_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: cours_educatif
-- ============================================================================
-- Cours éducatifs de santé
CREATE TABLE cours_educatif (
    id BINARY(16) NOT NULL,
    titre VARCHAR(255) NOT NULL,
    contenu LONGTEXT NOT NULL,
    score_pour_badge INT NOT NULL,
    date_creation DATETIME NOT NULL,
    categorie_sante_id BINARY(16) NOT NULL,
    medecin_validateur_id INT DEFAULT NULL,
    INDEX IDX_B5621BF9EF7C17 (categorie_sante_id),
    INDEX IDX_B5621BFF3E3919 (medecin_validateur_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: question_quiz
-- ============================================================================
-- Questions de quiz pour les cours éducatifs
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
    INDEX IDX_FAFC177D9AF2C787 (cours_educatif_id),
    INDEX IDX_FAFC177DFF3E3919 (medecin_validateur_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: progression_utilisateur
-- ============================================================================
-- Progression des utilisateurs dans les cours éducatifs
CREATE TABLE progression_utilisateur (
    id BINARY(16) NOT NULL,
    score_max INT NOT NULL,
    nb_tentatives INT NOT NULL,
    badge_nom VARCHAR(255) DEFAULT NULL,
    date_obtention DATETIME DEFAULT NULL,
    est_complete TINYINT NOT NULL,
    utilisateur_id INT NOT NULL,
    categorie_sante_id BINARY(16) NOT NULL,
    INDEX IDX_BC58001FFB88E14F (utilisateur_id),
    INDEX IDX_BC58001FF9EF7C17 (categorie_sante_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: reponse_utilisateur
-- ============================================================================
-- Réponses des utilisateurs aux questions de quiz
CREATE TABLE reponse_utilisateur (
    id BINARY(16) NOT NULL,
    reponse_choisie VARCHAR(255) NOT NULL,
    est_correcte TINYINT NOT NULL,
    points_obtenus INT NOT NULL,
    date_reponse DATETIME NOT NULL,
    utilisateur_id INT NOT NULL,
    question_id BINARY(16) NOT NULL,
    INDEX IDX_14B756B6FB88E14F (utilisateur_id),
    INDEX IDX_14B756B61E27F6BF (question_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: evenement
-- ============================================================================
-- Événements médicaux et conférences
CREATE TABLE evenement (
    id CHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT DEFAULT NULL,
    is_active TINYINT NOT NULL,
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
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: participant
-- ============================================================================
-- Participants aux événements
CREATE TABLE participant (
    id INT AUTO_INCREMENT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL,
    evenement_id CHAR(36) NOT NULL,
    INDEX IDX_D79F6B11FD02F13 (evenement_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: categorie_produit
-- ============================================================================
-- Catégories de produits pour l'e-commerce
CREATE TABLE categorie_produit (
    id INT AUTO_INCREMENT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description VARCHAR(500) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: produit
-- ============================================================================
-- Produits médicaux en vente
CREATE TABLE produit (
    id INT AUTO_INCREMENT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description LONGTEXT DEFAULT NULL,
    prix NUMERIC(10, 2) NOT NULL,
    stock INT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    categorie_id INT NOT NULL,
    INDEX IDX_29A5EC27BCF5E72D (categorie_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: commande_produit
-- ============================================================================
-- Commandes de produits
CREATE TABLE commande_produit (
    id INT AUTO_INCREMENT NOT NULL,
    date_commande DATETIME NOT NULL,
    statut VARCHAR(255) NOT NULL,
    montant_total NUMERIC(10, 2) DEFAULT NULL,
    adresse_livraison LONGTEXT DEFAULT NULL,
    telephone VARCHAR(32) DEFAULT NULL,
    pays VARCHAR(64) DEFAULT NULL,
    mode_paiement VARCHAR(32) DEFAULT NULL,
    
    -- Informations de livraison
    delivery_city VARCHAR(128) DEFAULT NULL,
    delivery_carrier VARCHAR(32) DEFAULT 'STANDARD' NOT NULL,
    delivery_traffic_level VARCHAR(16) DEFAULT 'MEDIUM' NOT NULL,
    delivery_cutoff_applied TINYINT DEFAULT 0 NOT NULL,
    delivery_eta_at DATETIME DEFAULT NULL,
    delivery_committed_at DATETIME DEFAULT NULL,
    delivery_delay_penalty_points INT DEFAULT 0 NOT NULL,
    delivery_sla_breached TINYINT DEFAULT 0 NOT NULL,
    delivered_at DATETIME DEFAULT NULL,
    
    utilisateur_id INT NOT NULL,
    INDEX IDX_DF1E9E87FB88E14F (utilisateur_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: ligne_commande
-- ============================================================================
-- Lignes de commande (détails des produits commandés)
CREATE TABLE ligne_commande (
    id INT AUTO_INCREMENT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire NUMERIC(10, 2) NOT NULL,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    INDEX IDX_3170B74B82EA2E54 (commande_id),
    INDEX IDX_3170B74BF347EFB (produit_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: avis_produit
-- ============================================================================
-- Avis et notes des produits par les utilisateurs
CREATE TABLE avis_produit (
    id INT AUTO_INCREMENT NOT NULL,
    note SMALLINT NOT NULL,
    commentaire LONGTEXT DEFAULT NULL,
    date_creation DATETIME NOT NULL,
    produit_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    INDEX IDX_2A67C21F347EFB (produit_id),
    INDEX IDX_2A67C21FB88E14F (utilisateur_id),
    UNIQUE INDEX uniq_avis_produit_user (produit_id, utilisateur_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: promo_code
-- ============================================================================
-- Codes promotionnels pour les commandes
CREATE TABLE promo_code (
    id INT AUTO_INCREMENT NOT NULL,
    code VARCHAR(50) NOT NULL,
    rate NUMERIC(5, 2) NOT NULL,
    start_at DATETIME DEFAULT NULL,
    end_at DATETIME DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0 NOT NULL,
    active TINYINT DEFAULT 1 NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE INDEX UNIQ_3D8C939E77153098 (code),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: webauthn_credential
-- ============================================================================
-- Credentials WebAuthn pour l'authentification biométrique
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
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================================
-- TABLE: messenger_messages
-- ============================================================================
-- Messages Symfony Messenger pour les tâches asynchrones
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
) DEFAULT CHARACTER SET utf8mb4;

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

-- Catégorie Santé
ALTER TABLE categorie_sante 
    ADD CONSTRAINT FK_3A70DFBBFC29C013 
    FOREIGN KEY (cree_par_id) REFERENCES utilisateur (id);

ALTER TABLE categorie_sante 
    ADD CONSTRAINT FK_3A70DFBB5ED9CBB3 
    FOREIGN KEY (approuve_par_id) REFERENCES utilisateur (id);

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

-- ============================================================================
-- FIN DU SCHÉMA
-- ============================================================================
