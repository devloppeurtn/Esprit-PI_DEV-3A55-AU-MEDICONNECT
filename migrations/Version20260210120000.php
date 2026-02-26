<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add email verification fields to utilisateur
 */
final class Version20260210120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add verification_token, verification_token_expires_at, email_verified for email verification on signup';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD verification_token VARCHAR(100) DEFAULT NULL, ADD verification_token_expires_at DATETIME DEFAULT NULL, ADD email_verified TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE utilisateur SET email_verified = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP verification_token, DROP verification_token_expires_at, DROP email_verified');
    }
}
