<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add maxParticipants field to evenement table';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $columns = $sm->listTableColumns('evenement');

        if (!isset($columns['max_participants'])) {
            $this->addSql('ALTER TABLE evenement ADD max_participants INT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $columns = $sm->listTableColumns('evenement');

        if (isset($columns['max_participants'])) {
            $this->addSql('ALTER TABLE evenement DROP COLUMN max_participants');
        }
    }
}
