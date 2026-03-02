<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create avis_medecin table for patient doctor ratings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE avis_medecin (
            id INT AUTO_INCREMENT NOT NULL, 
            medecin_id VARCHAR(36) NOT NULL, 
            patient_id VARCHAR(36) NOT NULL, 
            note SMALLINT NOT NULL, 
            commentaire LONGTEXT DEFAULT NULL, 
            date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            UNIQUE INDEX uniq_avis_medecin_patient (medecin_id, patient_id), 
            INDEX IDX_4C5E4C4A4F31C15 (medecin_id), 
            INDEX IDX_4C5E4C4A6B899279 (patient_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE avis_medecin ADD CONSTRAINT FK_4C5E4C4A4F31C15 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis_medecin ADD CONSTRAINT FK_4C5E4C4A6B899279 FOREIGN KEY (patient_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE avis_medecin');
    }
}
