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
