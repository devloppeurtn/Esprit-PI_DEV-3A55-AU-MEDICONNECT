-- ============================================================================
-- SCRIPT DE CORRECTION DES COLONNES MANQUANTES
-- ============================================================================
-- Ce script ajoute toutes les colonnes manquantes de manière sécurisée
-- ============================================================================

USE mediconnect;

-- ============================================================================
-- 1. Ajouter la colonne discr à utilisateur (si elle n'existe pas)
-- ============================================================================
SET @dbname = DATABASE();
SET @tablename = 'utilisateur';
SET @columnname = 'discr';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(255) NOT NULL DEFAULT "patient" AFTER notif_promo_enabled')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Mettre à jour les valeurs de discr selon le rôle
UPDATE utilisateur SET discr = 'admin' WHERE role LIKE '%ADMIN%' AND discr = 'patient';
UPDATE utilisateur SET discr = 'medecin' WHERE role LIKE '%MEDECIN%' AND discr = 'patient';
UPDATE utilisateur SET discr = 'secretaire' WHERE role LIKE '%SECRETAIRE%' AND discr = 'patient';
UPDATE utilisateur SET discr = 'organisateur' WHERE role LIKE '%ORGANISATEUR%' AND discr = 'patient';

-- ============================================================================
-- 2. Ajouter les colonnes manquantes à commande_produit
-- ============================================================================

-- Colonne: code_promo_id
SET @tablename = 'commande_produit';
SET @columnname = 'code_promo_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT DEFAULT NULL AFTER utilisateur_id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Colonne: montant_reduction
SET @columnname = 'montant_reduction';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DECIMAL(10,2) DEFAULT 0.00 AFTER code_promo_id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Colonne: montant_avant_reduction
SET @columnname = 'montant_avant_reduction';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DECIMAL(10,2) DEFAULT NULL AFTER montant_reduction')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================================================
-- VÉRIFICATION FINALE
-- ============================================================================
SELECT 'Script de correction exécuté avec succès!' AS Status;

-- Afficher les colonnes de commande_produit pour vérification
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'mediconnect' 
  AND TABLE_NAME = 'commande_produit'
ORDER BY ORDINAL_POSITION;
