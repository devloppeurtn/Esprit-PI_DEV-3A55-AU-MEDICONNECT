-- Ajouter la colonne discr manquante à la table utilisateur
ALTER TABLE utilisateur 
ADD COLUMN discr VARCHAR(255) NOT NULL DEFAULT 'patient' AFTER notif_promo_enabled;

-- Mettre à jour les valeurs selon le rôle
UPDATE utilisateur SET discr = 'admin' WHERE role LIKE '%ADMIN%';
UPDATE utilisateur SET discr = 'medecin' WHERE role LIKE '%MEDECIN%';
UPDATE utilisateur SET discr = 'secretaire' WHERE role LIKE '%SECRETAIRE%';
UPDATE utilisateur SET discr = 'patient' WHERE role LIKE '%PATIENT%';
UPDATE utilisateur SET discr = 'organisateur' WHERE role LIKE '%ORGANISATEUR%';
