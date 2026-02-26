<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225153318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE webauthn_credential (public_key_credential_id LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, transports JSON NOT NULL, attestation_type VARCHAR(255) NOT NULL, trust_path JSON NOT NULL, aaguid TINYTEXT NOT NULL, credential_public_key LONGTEXT NOT NULL, user_handle VARCHAR(255) NOT NULL, counter INT NOT NULL, other_ui JSON DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avis_produit CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE avis_produit ADD CONSTRAINT FK_2A67C21F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis_produit ADD CONSTRAINT FK_2A67C21FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis_produit RENAME INDEX idx_e49808a2f347efb TO IDX_2A67C21F347EFB');
        $this->addSql('ALTER TABLE avis_produit RENAME INDEX idx_e49808afb88e14f TO IDX_2A67C21FB88E14F');
        $this->addSql('DROP INDEX IDX_COMMANDE_DELIVERY_SLA ON commande_produit');
        $this->addSql('DROP INDEX IDX_COMMANDE_DELIVERY_ETA ON commande_produit');
        $this->addSql('ALTER TABLE commande_produit CHANGE delivery_eta_at delivery_eta_at DATETIME DEFAULT NULL, CHANGE delivery_committed_at delivery_committed_at DATETIME DEFAULT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A67750B79F FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A691EF7EAA FOREIGN KEY (rendez_vous_id) REFERENCES rendez_vous (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A64F31A84 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE document_patient ADD CONSTRAINT FK_BEB571377750B79F FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dossier_medical ADD CONSTRAINT FK_3581EE626B899279 FOREIGN KEY (patient_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A24F31A84 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2A90F02B2 FOREIGN KEY (secretaire_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE medicament_actuel ADD CONSTRAINT FK_33D025A47750B79F FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE medicament_actuel ADD CONSTRAINT FK_33D025A42BF23B8F FOREIGN KEY (ordonnance_id) REFERENCES ordonnance (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_924B326C62FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177D9AF2C787 FOREIGN KEY (cours_educatif_id) REFERENCES cours_educatif (id)');
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177DFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rapport_medical ADD CONSTRAINT FK_C0B673962FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A6B899279 FOREIGN KEY (patient_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A4F31A84 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse_utilisateur ADD CONSTRAINT FK_14B756B6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE reponse_utilisateur ADD CONSTRAINT FK_14B756B61E27F6BF FOREIGN KEY (question_id) REFERENCES question_quiz (id)');
        $this->addSql('ALTER TABLE utilisateur ADD biometric_enabled TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE webauthn_credential');
        $this->addSql('ALTER TABLE avis_produit DROP FOREIGN KEY FK_2A67C21F347EFB');
        $this->addSql('ALTER TABLE avis_produit DROP FOREIGN KEY FK_2A67C21FB88E14F');
        $this->addSql('ALTER TABLE avis_produit CHANGE date_creation date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE avis_produit RENAME INDEX idx_2a67c21f347efb TO IDX_E49808A2F347EFB');
        $this->addSql('ALTER TABLE avis_produit RENAME INDEX idx_2a67c21fb88e14f TO IDX_E49808AFB88E14F');
        $this->addSql('ALTER TABLE commande_produit CHANGE delivery_eta_at delivery_eta_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivery_committed_at delivery_committed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_COMMANDE_DELIVERY_SLA ON commande_produit (delivery_sla_breached)');
        $this->addSql('CREATE INDEX IDX_COMMANDE_DELIVERY_ETA ON commande_produit (delivery_eta_at)');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A67750B79F');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A691EF7EAA');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A64F31A84');
        $this->addSql('ALTER TABLE cours_educatif DROP FOREIGN KEY FK_B5621BF9EF7C17');
        $this->addSql('ALTER TABLE cours_educatif DROP FOREIGN KEY FK_B5621BFF3E3919');
        $this->addSql('ALTER TABLE document_patient DROP FOREIGN KEY FK_BEB571377750B79F');
        $this->addSql('ALTER TABLE dossier_medical DROP FOREIGN KEY FK_3581EE626B899279');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A24F31A84');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A2A90F02B2');
        $this->addSql('ALTER TABLE medicament_actuel DROP FOREIGN KEY FK_33D025A47750B79F');
        $this->addSql('ALTER TABLE medicament_actuel DROP FOREIGN KEY FK_33D025A42BF23B8F');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_924B326C62FF6CDF');
        $this->addSql('ALTER TABLE progression_utilisateur DROP FOREIGN KEY FK_BC58001FFB88E14F');
        $this->addSql('ALTER TABLE progression_utilisateur DROP FOREIGN KEY FK_BC58001FF9EF7C17');
        $this->addSql('ALTER TABLE question_quiz DROP FOREIGN KEY FK_FAFC177D9AF2C787');
        $this->addSql('ALTER TABLE question_quiz DROP FOREIGN KEY FK_FAFC177DFF3E3919');
        $this->addSql('ALTER TABLE rapport_medical DROP FOREIGN KEY FK_C0B673962FF6CDF');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A6B899279');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A4F31A84');
        $this->addSql('ALTER TABLE reponse_utilisateur DROP FOREIGN KEY FK_14B756B6FB88E14F');
        $this->addSql('ALTER TABLE reponse_utilisateur DROP FOREIGN KEY FK_14B756B61E27F6BF');
        $this->addSql('ALTER TABLE utilisateur DROP biometric_enabled');
    }
}
