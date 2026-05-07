<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Google OAuth and Google Authenticator fields to utilisateur';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        if (in_array('utilisateur', $tables, true)) {
            $columns = $sm->listTableColumns('utilisateur');
            if (!array_key_exists('google_id', $columns)) {
                $this->addSql('ALTER TABLE utilisateur ADD google_id VARCHAR(255) DEFAULT NULL');
            }
            if (!array_key_exists('google_authenticator_secret', $columns)) {
                $this->addSql('ALTER TABLE utilisateur ADD google_authenticator_secret VARCHAR(64) DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        if (in_array('utilisateur', $tables, true)) {
            $columns = $sm->listTableColumns('utilisateur');
            if (array_key_exists('google_authenticator_secret', $columns)) {
                $this->addSql('ALTER TABLE utilisateur DROP google_authenticator_secret');
            }
            if (array_key_exists('google_id', $columns)) {
                $this->addSql('ALTER TABLE utilisateur DROP google_id');
            }
        }
    }
}
