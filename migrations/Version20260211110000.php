<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create avis_produit table for product comments and star ratings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE avis_produit (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, utilisateur_id INT NOT NULL, note SMALLINT NOT NULL, commentaire LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E49808A2F347EFB (produit_id), INDEX IDX_E49808AFB88E14F (utilisateur_id), UNIQUE INDEX uniq_avis_produit_user (produit_id, utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avis_produit ADD CONSTRAINT FK_E49808A2F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis_produit ADD CONSTRAINT FK_E49808AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis_produit DROP FOREIGN KEY FK_E49808A2F347EFB');
        $this->addSql('ALTER TABLE avis_produit DROP FOREIGN KEY FK_E49808AFB88E14F');
        $this->addSql('DROP TABLE avis_produit');
    }
}
