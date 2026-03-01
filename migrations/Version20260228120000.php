<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add biometric fields and WebAuthn credential table for Module 1';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        if (!in_array('webauthn_credential', $tables, true)) {
            $this->addSql(
                'CREATE TABLE webauthn_credential (
                    public_key_credential_id LONGTEXT NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    transports JSON NOT NULL,
                    attestation_type VARCHAR(255) NOT NULL,
                    trust_path JSON NOT NULL,
                    aaguid TINYTEXT NOT NULL,
                    credential_public_key LONGTEXT NOT NULL,
                    user_handle VARCHAR(255) NOT NULL,
                    counter INT NOT NULL,
                    other_ui JSON DEFAULT NULL,
                    id INT AUTO_INCREMENT NOT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
        }

        if (in_array('utilisateur', $tables, true)) {
            $columns = $sm->listTableColumns('utilisateur');
            if (!array_key_exists('biometric_enabled', $columns)) {
                $this->addSql('ALTER TABLE utilisateur ADD biometric_enabled TINYINT DEFAULT 0 NOT NULL');
            }
            if (!array_key_exists('face_embedding', $columns)) {
                $this->addSql('ALTER TABLE utilisateur ADD face_embedding JSON DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        if (in_array('utilisateur', $tables, true)) {
            $columns = $sm->listTableColumns('utilisateur');
            if (array_key_exists('face_embedding', $columns)) {
                $this->addSql('ALTER TABLE utilisateur DROP face_embedding');
            }
            if (array_key_exists('biometric_enabled', $columns)) {
                $this->addSql('ALTER TABLE utilisateur DROP biometric_enabled');
            }
        }

        if (in_array('webauthn_credential', $tables, true)) {
            $this->addSql('DROP TABLE webauthn_credential');
        }
    }
}
