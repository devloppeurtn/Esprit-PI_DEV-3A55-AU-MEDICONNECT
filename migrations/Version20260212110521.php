<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212110521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Rendre la migration idempotente : si les FK principaux existent déjà, on considère la migration appliquée.
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        if (in_array('consultation', $tables, true)) {
            $existingFks = array_map(
                static fn ($fk) => strtoupper($fk->getName()),
                $sm->listTableForeignKeys('consultation')
            );

            // Si cette contrainte existe déjà, on suppose que la migration a déjà été appliquée manuellement.
            if (in_array('FK_964685A67750B79F', $existingFks, true)) {
                return;
            }
        }

        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A67750B79F FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A691EF7EAA FOREIGN KEY (rendez_vous_id) REFERENCES rendez_vous (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A64F31A84 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE document_patient ADD CONSTRAINT FK_BEB571377750B79F FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dossier_medical ADD CONSTRAINT FK_3581EE626B899279 FOREIGN KEY (patient_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evenement CHANGE id id CHAR(36) NOT NULL, CHANGE is_active is_active TINYINT NOT NULL, CHANGE event_date event_date DATE DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE statut statut VARCHAR(20) NOT NULL, CHANGE approuve_at approuve_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE evenement RENAME INDEX idx_b26681e2b80b8b0 TO IDX_B26681E5ED9CBB3');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A24F31A84 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2A90F02B2 FOREIGN KEY (secretaire_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE medicament_actuel ADD CONSTRAINT FK_33D025A47750B79F FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE medicament_actuel ADD CONSTRAINT FK_33D025A42BF23B8F FOREIGN KEY (ordonnance_id) REFERENCES ordonnance (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_924B326C62FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participant CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177D9AF2C787 FOREIGN KEY (cours_educatif_id) REFERENCES cours_educatif (id)');
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177DFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rapport_medical ADD CONSTRAINT FK_C0B673962FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A6B899279 FOREIGN KEY (patient_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A4F31A84 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse_utilisateur ADD CONSTRAINT FK_14B756B6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE reponse_utilisateur ADD CONSTRAINT FK_14B756B61E27F6BF FOREIGN KEY (question_id) REFERENCES question_quiz (id)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B34F31A84 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A67750B79F');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A691EF7EAA');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A64F31A84');
        $this->addSql('ALTER TABLE cours_educatif DROP FOREIGN KEY FK_B5621BF9EF7C17');
        $this->addSql('ALTER TABLE cours_educatif DROP FOREIGN KEY FK_B5621BFF3E3919');
        $this->addSql('ALTER TABLE document_patient DROP FOREIGN KEY FK_BEB571377750B79F');
        $this->addSql('ALTER TABLE dossier_medical DROP FOREIGN KEY FK_3581EE626B899279');
        $this->addSql('ALTER TABLE evenement CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:string)\', CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT \'EN_ATTENTE\' NOT NULL, CHANGE approuve_at approuve_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE event_date event_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE evenement RENAME INDEX idx_b26681e5ed9cbb3 TO IDX_B26681E2B80B8B0');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A24F31A84');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A2A90F02B2');
        $this->addSql('ALTER TABLE medicament_actuel DROP FOREIGN KEY FK_33D025A47750B79F');
        $this->addSql('ALTER TABLE medicament_actuel DROP FOREIGN KEY FK_33D025A42BF23B8F');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_924B326C62FF6CDF');
        $this->addSql('ALTER TABLE participant CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE progression_utilisateur DROP FOREIGN KEY FK_BC58001FFB88E14F');
        $this->addSql('ALTER TABLE progression_utilisateur DROP FOREIGN KEY FK_BC58001FF9EF7C17');
        $this->addSql('ALTER TABLE question_quiz DROP FOREIGN KEY FK_FAFC177D9AF2C787');
        $this->addSql('ALTER TABLE question_quiz DROP FOREIGN KEY FK_FAFC177DFF3E3919');
        $this->addSql('ALTER TABLE rapport_medical DROP FOREIGN KEY FK_C0B673962FF6CDF');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A6B899279');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A4F31A84');
        $this->addSql('ALTER TABLE reponse_utilisateur DROP FOREIGN KEY FK_14B756B6FB88E14F');
        $this->addSql('ALTER TABLE reponse_utilisateur DROP FOREIGN KEY FK_14B756B61E27F6BF');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B34F31A84');
    }
}
