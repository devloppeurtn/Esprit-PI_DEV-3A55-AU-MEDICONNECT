<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260210180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure event_date column exists on evenement (safe add)';
    }

    public function up(Schema $schema): void
    {
        // Add column if not exists (MySQL syntax)
        $this->addSql("ALTER TABLE evenement ADD COLUMN IF NOT EXISTS event_date DATE DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenement DROP COLUMN event_date');
    }
}
