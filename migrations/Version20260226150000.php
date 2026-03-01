<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module 4: add event type and attachment columns';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $columns = [];
        foreach ($sm->listTableColumns('evenement') as $column) {
            $columns[$column->getName()] = true;
        }

        if (!isset($columns['type_evenement'])) {
            $this->addSql('ALTER TABLE evenement ADD type_evenement VARCHAR(64) DEFAULT NULL');
        }

        if (!isset($columns['attachment_path'])) {
            $this->addSql('ALTER TABLE evenement ADD attachment_path VARCHAR(255) DEFAULT NULL');
        }

        if (!isset($columns['attachment_original_name'])) {
            $this->addSql('ALTER TABLE evenement ADD attachment_original_name VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $columns = [];
        foreach ($sm->listTableColumns('evenement') as $column) {
            $columns[$column->getName()] = true;
        }

        if (isset($columns['attachment_original_name'])) {
            $this->addSql('ALTER TABLE evenement DROP attachment_original_name');
        }

        if (isset($columns['attachment_path'])) {
            $this->addSql('ALTER TABLE evenement DROP attachment_path');
        }

        if (isset($columns['type_evenement'])) {
            $this->addSql('ALTER TABLE evenement DROP type_evenement');
        }
    }
}

