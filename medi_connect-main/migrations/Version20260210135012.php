<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210135012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie_sante (id BINARY(16) NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE cours_educatif (id BINARY(16) NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, score_pour_badge INT NOT NULL, date_creation DATETIME NOT NULL, categorie_sante_id BINARY(16) NOT NULL, medecin_validateur_id INT DEFAULT NULL, INDEX IDX_B5621BF9EF7C17 (categorie_sante_id), INDEX IDX_B5621BFF3E3919 (medecin_validateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE progression_utilisateur (id BINARY(16) NOT NULL, score_max INT NOT NULL, nb_tentatives INT NOT NULL, badge_nom VARCHAR(255) DEFAULT NULL, date_obtention DATETIME DEFAULT NULL, est_complete TINYINT NOT NULL, utilisateur_id INT NOT NULL, categorie_sante_id BINARY(16) NOT NULL, INDEX IDX_BC58001FFB88E14F (utilisateur_id), INDEX IDX_BC58001FF9EF7C17 (categorie_sante_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE question_quiz (id BINARY(16) NOT NULL, enonce VARCHAR(500) NOT NULL, options_reponses LONGTEXT NOT NULL, reponse_correcte VARCHAR(255) NOT NULL, explication LONGTEXT DEFAULT NULL, statut VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL, cours_educatif_id BINARY(16) NOT NULL, medecin_validateur_id INT DEFAULT NULL, INDEX IDX_FAFC177D9AF2C787 (cours_educatif_id), INDEX IDX_FAFC177DFF3E3919 (medecin_validateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, mot_de_passe_hash VARCHAR(255) NOT NULL, nom_complet VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, derniere_connexion DATETIME DEFAULT NULL, discr VARCHAR(255) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, date_naissance DATE DEFAULT NULL, adresse LONGTEXT DEFAULT NULL, specialite VARCHAR(255) DEFAULT NULL, adresse_cabinet LONGTEXT DEFAULT NULL, numero_licence VARCHAR(100) DEFAULT NULL, role_dans_evenement VARCHAR(255) DEFAULT NULL, presence_confirmee TINYINT DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177D9AF2C787 FOREIGN KEY (cours_educatif_id) REFERENCES cours_educatif (id)');
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177DFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cours_educatif DROP FOREIGN KEY FK_B5621BF9EF7C17');
        $this->addSql('ALTER TABLE cours_educatif DROP FOREIGN KEY FK_B5621BFF3E3919');
        $this->addSql('ALTER TABLE progression_utilisateur DROP FOREIGN KEY FK_BC58001FFB88E14F');
        $this->addSql('ALTER TABLE progression_utilisateur DROP FOREIGN KEY FK_BC58001FF9EF7C17');
        $this->addSql('ALTER TABLE question_quiz DROP FOREIGN KEY FK_FAFC177D9AF2C787');
        $this->addSql('ALTER TABLE question_quiz DROP FOREIGN KEY FK_FAFC177DFF3E3919');
        $this->addSql('DROP TABLE categorie_sante');
        $this->addSql('DROP TABLE cours_educatif');
        $this->addSql('DROP TABLE progression_utilisateur');
        $this->addSql('DROP TABLE question_quiz');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
