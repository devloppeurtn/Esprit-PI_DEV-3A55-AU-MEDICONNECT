<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create evenement and participant tables for MediConnect-azza integration';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        // En environnement où le schéma a déjà été créé manuellement,
        // on s'assure de repartir sur une base propre pour ces tables.
        if (in_array('participant', $tables, true)) {
            $this->addSql('DROP TABLE participant');
            // Rafraîchir la liste des tables après suppression
            $tables = $sm->listTableNames();
        }

        if (!in_array('evenement', $tables, true)) {
            $this->addSql("CREATE TABLE evenement (
                id CHAR(36) NOT NULL COMMENT '(DC2Type:string)',
                title VARCHAR(255) NOT NULL,
                content LONGTEXT DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                event_date DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
                location VARCHAR(255) DEFAULT NULL,
                event_time VARCHAR(10) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        }

        if (!in_array('participant', $tables, true)) {
            $this->addSql("CREATE TABLE participant (
                id INT AUTO_INCREMENT NOT NULL,
                evenement_id CHAR(36) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(180) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_D79F6B11FD02F13 (evenement_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        }

        // Ajout de la contrainte FK uniquement si la table participant existe déjà
        if (in_array('participant', $tables, true)) {
            $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11FD02F13');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE participant');
    }
}
