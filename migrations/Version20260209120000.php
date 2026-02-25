<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add reset password token fields to utilisateur
 */
final class Version20260209120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reset_token and reset_token_expires_at to utilisateur for forgot password feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD reset_token VARCHAR(100) DEFAULT NULL, ADD reset_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP reset_token, DROP reset_token_expires_at');
    }
}
