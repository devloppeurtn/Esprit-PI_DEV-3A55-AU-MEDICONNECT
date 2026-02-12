<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update participant foreign key from module_four_id to evenement_id';
    }

    public function up(Schema $schema): void
    {
        // Drop old foreign key and update column name
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY `FK_D79F6B114ED73EBD`');
        $this->addSql('DROP INDEX FK_D79F6B114ED73EBD ON participant');
        $this->addSql('ALTER TABLE participant CHANGE module_four_id evenement_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D79F6B11FD02F13 ON participant (evenement_id)');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        // Reverse the changes (keep evenement as target)
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY `FK_D79F6B11FD02F13`');
        $this->addSql('DROP INDEX IDX_D79F6B11FD02F13 ON participant');
        $this->addSql('ALTER TABLE participant CHANGE evenement_id module_four_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B114ED73EBD FOREIGN KEY (module_four_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX FK_D79F6B114ED73EBD ON participant (module_four_id)');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
