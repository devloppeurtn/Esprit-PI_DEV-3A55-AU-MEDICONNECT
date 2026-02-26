<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211225635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module e-commerce: categorie_produit, produit, commande_produit, ligne_commande, avis_produit, promo_code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categorie_produit (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description VARCHAR(500) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, prix NUMERIC(10, 2) NOT NULL, stock INT NOT NULL, image VARCHAR(255) DEFAULT NULL, INDEX IDX_29A5EC27BCF5E72D (categorie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande_produit (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, date_commande DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, montant_total NUMERIC(10, 2) DEFAULT NULL, adresse_livraison LONGTEXT DEFAULT NULL, telephone VARCHAR(32) DEFAULT NULL, pays VARCHAR(64) DEFAULT NULL, mode_paiement VARCHAR(32) DEFAULT NULL, INDEX IDX_DF1E9E87FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ligne_commande (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, INDEX IDX_3170B74B82EA2E54 (commande_id), INDEX IDX_3170B74BF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE avis_produit (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, utilisateur_id INT NOT NULL, note SMALLINT NOT NULL, commentaire LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, INDEX IDX_2A67C21F347EFB (produit_id), INDEX IDX_2A67C21FB88E14F (utilisateur_id), UNIQUE INDEX uniq_avis_produit_user (produit_id, utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE promo_code (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, rate NUMERIC(5, 2) NOT NULL, start_at DATETIME DEFAULT NULL, end_at DATETIME DEFAULT NULL, usage_limit INT DEFAULT NULL, used_count INT DEFAULT 0 NOT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_3D8C939E77153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_produit (id)');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E87FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande_produit (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74BF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE avis_produit ADD CONSTRAINT FK_2A67C21F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis_produit ADD CONSTRAINT FK_2A67C21FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis_produit DROP FOREIGN KEY FK_2A67C21F347EFB');
        $this->addSql('ALTER TABLE avis_produit DROP FOREIGN KEY FK_2A67C21FB88E14F');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E87FB88E14F');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B82EA2E54');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74BF347EFB');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27BCF5E72D');
        $this->addSql('DROP TABLE avis_produit');
        $this->addSql('DROP TABLE promo_code');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('DROP TABLE commande_produit');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE categorie_produit');
    }
}
