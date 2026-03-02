<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add statut, organisateur, approuvePar to evenement for admin validation workflow';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $columns = $sm->listTableColumns('evenement');
        $foreignKeys = $sm->listTableForeignKeys('evenement');
        $fkNames = array_map(static fn ($fk) => $fk->getName(), $foreignKeys);

        // Ensure referenced table uses InnoDB (required for foreign keys; fixes MySQL 1824)
        $this->addSql('ALTER TABLE utilisateur ENGINE=InnoDB');

        if (!isset($columns['statut'])) {
            $this->addSql('ALTER TABLE evenement ADD statut VARCHAR(20) NOT NULL DEFAULT \'EN_ATTENTE\'');
        }
        if (!isset($columns['organisateur_id'])) {
            $this->addSql('ALTER TABLE evenement ADD organisateur_id INT DEFAULT NULL');
        }
        if (!isset($columns['approuve_par_id'])) {
            $this->addSql('ALTER TABLE evenement ADD approuve_par_id INT DEFAULT NULL');
        }
        if (!isset($columns['approuve_at'])) {
            $this->addSql('ALTER TABLE evenement ADD approuve_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }

        if (!in_array('FK_B26681ED936B2FA', $fkNames, true)) {
            $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681ED936B2FA FOREIGN KEY (organisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_B26681ED936B2FA ON evenement (organisateur_id)');
        }
        if (!in_array('FK_B26681E2B80B8B0', $fkNames, true)) {
            $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E2B80B8B0 FOREIGN KEY (approuve_par_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_B26681E2B80B8B0 ON evenement (approuve_par_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681ED936B2FA');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E2B80B8B0');
        $this->addSql('DROP INDEX IDX_B26681ED936B2FA ON evenement');
        $this->addSql('DROP INDEX IDX_B26681E2B80B8B0 ON evenement');
        $this->addSql('ALTER TABLE evenement DROP statut, DROP organisateur_id, DROP approuve_par_id, DROP approuve_at');
    }
}
