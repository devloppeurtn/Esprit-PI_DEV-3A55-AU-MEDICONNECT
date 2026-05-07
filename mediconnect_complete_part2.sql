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

