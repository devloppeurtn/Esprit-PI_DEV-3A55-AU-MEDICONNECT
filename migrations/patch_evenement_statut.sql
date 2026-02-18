-- Ajout des colonnes statut, organisateur, approuvePar pour la validation des événements
-- Exécuter ce fichier si doctrine:migrations:migrate ne peut pas être utilisé

ALTER TABLE evenement ADD COLUMN statut VARCHAR(20) NOT NULL DEFAULT 'EN_ATTENTE';
ALTER TABLE evenement ADD COLUMN organisateur_id INT DEFAULT NULL;
ALTER TABLE evenement ADD COLUMN approuve_par_id INT DEFAULT NULL;
ALTER TABLE evenement ADD COLUMN approuve_at DATETIME DEFAULT NULL;

ALTER TABLE evenement ADD CONSTRAINT FK_B26681ED936B2FA FOREIGN KEY (organisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL;
ALTER TABLE evenement ADD CONSTRAINT FK_B26681E2B80B8B0 FOREIGN KEY (approuve_par_id) REFERENCES utilisateur (id) ON DELETE SET NULL;
CREATE INDEX IDX_B26681ED936B2FA ON evenement (organisateur_id);
CREATE INDEX IDX_B26681E2B80B8B0 ON evenement (approuve_par_id);
