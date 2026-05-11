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

