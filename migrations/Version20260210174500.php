<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260210174500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create participant table linked to evenement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE participant (
            id INT AUTO_INCREMENT NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(180) NOT NULL,
            evenement_id CHAR(36) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_PARTICIPANT_EVENEMENT FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_PARTICIPANT_EVENEMENT');
        $this->addSql('DROP TABLE participant');
    }
}
