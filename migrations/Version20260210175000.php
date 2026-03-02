<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260210175000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add eventDate column to evenement table';
    }

    public function up(Schema $schema): void
    {
        // Only add if it doesn't exist (MySQL)
        $this->addSql("ALTER TABLE evenement ADD COLUMN IF NOT EXISTS event_date DATE DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenement DROP COLUMN event_date');
    }
}
