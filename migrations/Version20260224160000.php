<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create avis_evenement table for event feedback';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = array_change_key_case(array_flip($sm->listTableNames()), CASE_LOWER);

        if (!isset($tables['avis_evenement'])) {
            $this->addSql('CREATE TABLE avis_evenement (
                id INT AUTO_INCREMENT NOT NULL,
                evenement_id VARCHAR(36) NOT NULL,
                participant_id INT NOT NULL,
                note SMALLINT NOT NULL,
                commentaire LONGTEXT DEFAULT NULL,
                date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX uniq_avis_evenement_participant (evenement_id, participant_id),
                INDEX IDX_AVIS_EVENEMENT_EVT (evenement_id),
                INDEX IDX_AVIS_EVENEMENT_PART (participant_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

            $this->addSql('ALTER TABLE avis_evenement ADD CONSTRAINT FK_AVIS_EVENEMENT_EVT 
                FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE avis_evenement ADD CONSTRAINT FK_AVIS_EVENEMENT_PART 
                FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = array_change_key_case(array_flip($sm->listTableNames()), CASE_LOWER);

        if (isset($tables['avis_evenement'])) {
            $this->addSql('DROP TABLE IF EXISTS avis_evenement');
        }
    }
}
