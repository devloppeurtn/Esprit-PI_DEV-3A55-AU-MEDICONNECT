<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align notification.utilisateur_id avec le mapping Doctrine (anciennement destinataire_id)';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        if (!in_array('notification', $tables, true)) {
            return;
        }

        $columns = $sm->listTableColumns('notification');

        // Si la colonne utilisateur_id existe déjà, on ne fait rien.
        if (array_key_exists('utilisateur_id', $columns)) {
            return;
        }

        // Si on ne trouve pas destinataire_id, on ne tente pas de la renommer.
        if (!array_key_exists('destinataire_id', $columns)) {
            return;
        }

        // Nettoyer les contraintes/indices liés à l'ancien schéma si présents.
        try {
            $this->addSql('ALTER TABLE notification DROP FOREIGN KEY `FK_8A7B334DFB88E14F`');
        } catch (\Throwable $e) {
            // ignorer si la contrainte n'existe pas
        }

        try {
            $this->addSql('ALTER TABLE notification DROP FOREIGN KEY `FK_BF5476CAA4F84F6E`');
        } catch (\Throwable $e) {
            // ignorer si la contrainte n'existe pas
        }

        try {
            $this->addSql('DROP INDEX IDX_8A7B334DFB88E14F ON notification');
        } catch (\Throwable $e) {
            // ignorer
        }

        try {
            $this->addSql('DROP INDEX IDX_BF5476CAA4F84F6E ON notification');
        } catch (\Throwable $e) {
            // ignorer
        }

        try {
            $this->addSql('DROP INDEX IDX_NOTIFICATION_DESTINATAIRE_LU ON notification');
        } catch (\Throwable $e) {
            // ignorer
        }

        // Renommer la colonne destinataire_id -> utilisateur_id
        $this->addSql('ALTER TABLE notification CHANGE destinataire_id utilisateur_id INT NOT NULL');

        // Recréer la contrainte de clé étrangère vers utilisateur.id
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_BF5476CAFB88E14F ON notification (utilisateur_id)');
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        if (!in_array('notification', $tables, true)) {
            return;
        }

        $columns = $sm->listTableColumns('notification');

        if (!array_key_exists('utilisateur_id', $columns)) {
            return;
        }

        // Supprimer la contrainte et l'index recréés
        try {
            $this->addSql('ALTER TABLE notification DROP FOREIGN KEY `FK_BF5476CAFB88E14F`');
        } catch (\Throwable $e) {
        }

        try {
            $this->addSql('DROP INDEX IDX_BF5476CAFB88E14F ON notification');
        } catch (\Throwable $e) {
        }

        // Revenir à l'ancien nom de colonne si besoin
        $this->addSql('ALTER TABLE notification CHANGE utilisateur_id destinataire_id INT NOT NULL');
    }
}

