<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification table for in-app patient notifications';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        // Si la table notification existe déjà, on ne recrée pas la structure (environnement déjà provisionné)
        if (in_array('notification', $tables, true)) {
            return;
        }

        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, titre VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(20) NOT NULL, est_lu TINYINT(1) NOT NULL, date_creation DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", INDEX IDX_8A7B334DFB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_8A7B334DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification');
    }
}
