<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delivery SLA fields to commande_produit (ETA commit, carrier, traffic, penalty tracking)';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $columns = $sm->listTableColumns('commande_produit');
        $indexes = array_change_key_case($sm->listTableIndexes('commande_produit'), CASE_LOWER);

        if (!isset($columns['delivery_city'])) {
            $this->addSql('ALTER TABLE commande_produit ADD delivery_city VARCHAR(128) DEFAULT NULL');
        }
        if (!isset($columns['delivery_carrier'])) {
            $this->addSql("ALTER TABLE commande_produit ADD delivery_carrier VARCHAR(32) NOT NULL DEFAULT 'STANDARD'");
        }
        if (!isset($columns['delivery_traffic_level'])) {
            $this->addSql("ALTER TABLE commande_produit ADD delivery_traffic_level VARCHAR(16) NOT NULL DEFAULT 'MEDIUM'");
        }
        if (!isset($columns['delivery_cutoff_applied'])) {
            $this->addSql('ALTER TABLE commande_produit ADD delivery_cutoff_applied TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!isset($columns['delivery_eta_at'])) {
            $this->addSql("ALTER TABLE commande_produit ADD delivery_eta_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }
        if (!isset($columns['delivery_committed_at'])) {
            $this->addSql("ALTER TABLE commande_produit ADD delivery_committed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }
        if (!isset($columns['delivery_delay_penalty_points'])) {
            $this->addSql('ALTER TABLE commande_produit ADD delivery_delay_penalty_points INT NOT NULL DEFAULT 0');
        }
        if (!isset($columns['delivery_sla_breached'])) {
            $this->addSql('ALTER TABLE commande_produit ADD delivery_sla_breached TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!isset($columns['delivered_at'])) {
            $this->addSql("ALTER TABLE commande_produit ADD delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }

        if (!isset($indexes['idx_commande_delivery_eta'])) {
            $this->addSql('CREATE INDEX IDX_COMMANDE_DELIVERY_ETA ON commande_produit (delivery_eta_at)');
        }
        if (!isset($indexes['idx_commande_delivery_sla'])) {
            $this->addSql('CREATE INDEX IDX_COMMANDE_DELIVERY_SLA ON commande_produit (delivery_sla_breached)');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $columns = $sm->listTableColumns('commande_produit');
        $indexes = array_change_key_case($sm->listTableIndexes('commande_produit'), CASE_LOWER);

        if (isset($indexes['idx_commande_delivery_eta'])) {
            $this->addSql('DROP INDEX IDX_COMMANDE_DELIVERY_ETA ON commande_produit');
        }
        if (isset($indexes['idx_commande_delivery_sla'])) {
            $this->addSql('DROP INDEX IDX_COMMANDE_DELIVERY_SLA ON commande_produit');
        }

        $dropParts = [];
        foreach ([
            'delivery_city',
            'delivery_carrier',
            'delivery_traffic_level',
            'delivery_cutoff_applied',
            'delivery_eta_at',
            'delivery_committed_at',
            'delivery_delay_penalty_points',
            'delivery_sla_breached',
            'delivered_at',
        ] as $column) {
            if (isset($columns[$column])) {
                $dropParts[] = 'DROP ' . $column;
            }
        }

        if ($dropParts !== []) {
            $this->addSql('ALTER TABLE commande_produit ' . implode(', ', $dropParts));
        }
    }
}
