<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210161053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invitation (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(255) NOT NULL, token VARCHAR(64) NOT NULL, date_creation DATETIME NOT NULL, date_reponse DATETIME DEFAULT NULL, medecin_id INT NOT NULL, secretaire_id INT NOT NULL, UNIQUE INDEX UNIQ_F11D61A25F37A13B (token), INDEX IDX_F11D61A24F31A84 (medecin_id), INDEX IDX_F11D61A2A90F02B2 (secretaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A24F31A84 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2A90F02B2 FOREIGN KEY (secretaire_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        $this->addSql('ALTER TABLE cours_educatif ADD CONSTRAINT FK_B5621BFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE progression_utilisateur ADD CONSTRAINT FK_BC58001FF9EF7C17 FOREIGN KEY (categorie_sante_id) REFERENCES categorie_sante (id)');
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177D9AF2C787 FOREIGN KEY (cours_educatif_id) REFERENCES cours_educatif (id)');
        $this->addSql('ALTER TABLE question_quiz ADD CONSTRAINT FK_FAFC177DFF3E3919 FOREIGN KEY (medecin_validateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B34F31A84 FOREIGN KEY (medecin_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A24F31A84');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A2A90F02B2');
        $this->addSql('DROP TABLE invitation');
        $this->addSql('ALTER TABLE cours_educatif DROP FOREIGN KEY FK_B5621BF9EF7C17');
        $this->addSql('ALTER TABLE cours_educatif DROP FOREIGN KEY FK_B5621BFF3E3919');
        $this->addSql('ALTER TABLE progression_utilisateur DROP FOREIGN KEY FK_BC58001FFB88E14F');
        $this->addSql('ALTER TABLE progression_utilisateur DROP FOREIGN KEY FK_BC58001FF9EF7C17');
        $this->addSql('ALTER TABLE question_quiz DROP FOREIGN KEY FK_FAFC177D9AF2C787');
        $this->addSql('ALTER TABLE question_quiz DROP FOREIGN KEY FK_FAFC177DFF3E3919');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B34F31A84');
    }
}
