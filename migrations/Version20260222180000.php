<?php

declare(strict_types = 1)
;

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Création de la table planning_medecin pour la gestion des horaires des médecins.
 */
final class Version20260222180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create planning_medecin table — stores doctor schedule config (working hours, consultation duration, working days)';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        if (!in_array('planning_medecin', $tables, true)) {
            $this->addSql(
                'CREATE TABLE planning_medecin (
                    id INT AUTO_INCREMENT NOT NULL,
                    medecin_id INT NOT NULL,
                    heure_debut_matin TIME DEFAULT NULL,
                    heure_fin_matin TIME DEFAULT NULL,
                    heure_debut_apres_midi TIME DEFAULT NULL,
                    heure_fin_apres_midi TIME DEFAULT NULL,
                    duree_consultation INT NOT NULL DEFAULT 30,
                    jours_ouverture LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
                    UNIQUE INDEX UNIQ_planning_medecin_id (medecin_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );

            $this->addSql(
                'ALTER TABLE planning_medecin
                    ADD CONSTRAINT FK_planning_medecin_medecin
                    FOREIGN KEY (medecin_id)
                    REFERENCES utilisateur (id)
                    ON DELETE CASCADE'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        if (in_array('planning_medecin', $tables, true)) {
            $this->addSql('ALTER TABLE planning_medecin DROP FOREIGN KEY FK_planning_medecin_medecin');
            $this->addSql('DROP TABLE planning_medecin');
        }
    }
}
