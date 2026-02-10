<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Add medical knowledge module tables
 */
final class Version20260208224500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for medical knowledge and shared intelligence module: categorie_sante, cours_educatif, question_quiz, progression_utilisateur';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie_sante (id BINARY(16) NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('CREATE TABLE cours_educatif (id BINARY(16) NOT NULL, categorie_sante_id BINARY(16) NOT NULL, medecin_validateur_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, score_pour_badge INT NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_B5621BF9EF7C17 (categorie_sante_id), INDEX IDX_B5621BFF3E3919 (medecin_validateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('CREATE TABLE progression_utilisateur (id BINARY(16) NOT NULL, utilisateur_id INT NOT NULL, categorie_sante_id BINARY(16) NOT NULL, score_max INT NOT NULL, nb_tentatives INT NOT NULL, badge_nom VARCHAR(255) DEFAULT NULL, date_obtention DATETIME DEFAULT NULL, est_complete TINYINT(1) NOT NULL, INDEX IDX_BC58001FFB88E14F (utilisateur_id), INDEX IDX_BC58001FF9EF7C17 (categorie_sante_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('CREATE TABLE question_quiz (id BINARY(16) NOT NULL, cours_educatif_id BINARY(16) NOT NULL, medecin_validateur_id INT DEFAULT NULL, enonce VARCHAR(500) NOT NULL, options_reponses LONGTEXT NOT NULL, reponse_correcte VARCHAR(255) NOT NULL, explication LONGTEXT DEFAULT NULL, statut VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_FAFC177D9AF2C787 (cours_educatif_id), INDEX IDX_FAFC177DFF3E3919 (medecin_validateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
        
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177D9AF2C787 FOREIGN KEY (cours_educatif_id) REFERENCES cours_educatif (id)');
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177DFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
        
        $this->addSql('ALTER TABLE utilisateur CHANGE derniere_connexion derniere_connexion DATETIME DEFAULT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE specialite specialite VARCHAR(255) DEFAULT NULL, CHANGE numero_licence numero_licence VARCHAR(100) DEFAULT NULL, CHANGE role_dans_evenement role_dans_evenement VARCHAR(255) DEFAULT NULL');
        
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cours_educatif DROP FOREIGN KEY FK_B5621BF9EF7C17');
        $this->addSql('ALTER TABLE progression_utilisateur DROP FOREIGN KEY FK_BC58001FF9EF7C17');
        
        $this->addSql('ALTER TABLE question_quiz DROP FOREIGN KEY FK_FAFC177D9AF2C787');
        
        $this->addSql('DROP TABLE categorie_sante');
        $this->addSql('DROP TABLE cours_educatif');
        $this->addSql('DROP TABLE progression_utilisateur');
        $this->addSql('DROP TABLE question_quiz');
        
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE utilisateur CHANGE derniere_connexion derniere_connexion DATETIME DEFAULT \'NULL\', CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE date_naissance date_naissance DATE DEFAULT \'NULL\', CHANGE specialite specialite VARCHAR(255) DEFAULT \'NULL\', CHANGE numero_licence numero_licence VARCHAR(100) DEFAULT \'NULL\', CHANGE role_dans_evenement role_dans_evenement VARCHAR(255) DEFAULT \'NULL\'');
    }
}
