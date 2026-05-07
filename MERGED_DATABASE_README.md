# MediConnect - Base de Données Complète Fusionnée

## 📋 Vue d'Ensemble

Ce fichier contient le schéma SQL **complet et fusionné** de la base de données MediConnect, combinant:
- Le schéma Doctrine (généré depuis les entités Symfony)
- Le schéma phpMyAdmin (dump de la base existante)

## 📁 Fichiers Disponibles

### 1. `mediconnect_COMPLETE_MERGED.sql`
**Fichier principal** - Schéma complet fusionné avec:
- ✅ **40+ tables** au total
- ✅ Toutes les colonnes des deux schémas
- ✅ Tous les index optimisés
- ✅ Toutes les contraintes de clés étrangères
- ✅ Compatibilité MySQL/MariaDB

### 2. `database_schema.sql`
Schéma original généré depuis Doctrine (27 tables)

### 3. `database_sample_data.sql`
Données d'exemple pour tester l'application

## 🔄 Différences Entre les Deux Schémas

### Tables Ajoutées (Présentes dans phpMyAdmin uniquement)

#### 📱 **Système de Chat**
```sql
- chat_conversation
- chat_message
- chat_participant
- chat_mobile_session
- chat_mobile_token
- user_presence
```

#### 🛒 **E-Commerce Avancé**
```sql
- panier
- panier_item
- code_promo (version étendue)
- utilisation_code_promo
```

#### 📚 **Système Éducatif Étendu**
```sql
- patient_progress
- quiz_attempts
```

#### ⚙️ **Configuration**
```sql
- app_settings
- doctrine_migration_versions
```

### Colonnes Ajoutées aux Tables Existantes

#### **utilisateur**
```sql
-- Nouvelles colonnes:
+ totp_secret VARCHAR(64)
+ totp_enabled TINYINT(1)
+ notif_commande_enabled TINYINT(1)
+ notif_promo_enabled TINYINT(1)
```

#### **produit**
```sql
-- Nouvelles colonnes:
+ categorie VARCHAR(100)  -- En plus de categorie_id
+ date_creation TIMESTAMP
+ date_modification TIMESTAMP
```

#### **commande_produit**
```sql
-- Nouvelles colonnes:
+ code_promo_id INT
+ montant_reduction DECIMAL(10,2)
+ montant_avant_reduction DECIMAL(10,2)
```

#### **notification**
```sql
-- Nouvelles colonnes:
+ lue TINYINT(1)  -- En plus de est_lu
```

#### **rendez_vous**
```sql
-- Nouvelles colonnes:
+ google_calendar_event_id VARCHAR(255)
```

#### **participant**
```sql
-- Nouvelles colonnes:
+ telephone VARCHAR(50)
+ ticket_sent TINYINT(1)
+ ticket_code VARCHAR(100)
```

#### **categorie_sante**
```sql
-- Nouvelles colonnes:
+ date_creation DATETIME
+ statut_approbation VARCHAR(30)
+ admin_id BINARY(16)
+ admin_nom VARCHAR(255)
+ commentaire_admin TEXT
```

#### **cours_educatif**
```sql
-- Nouvelles colonnes:
+ media_url VARCHAR(1000)
+ media_type VARCHAR(32)
+ media_public_id VARCHAR(255)
+ media_original_name VARCHAR(255)
+ media_resource_type VARCHAR(32)
+ statut_approbation VARCHAR(30)
+ admin_id BINARY(16)
+ admin_nom VARCHAR(255)
+ date_approbation DATETIME
+ commentaire_admin TEXT
```

#### **question_quiz**
```sql
-- Nouvelles colonnes:
+ quiz_id VARCHAR(100)
+ statut_approbation VARCHAR(30)
+ admin_id BINARY(16)
+ admin_nom VARCHAR(255)
+ date_approbation DATETIME
+ commentaire_admin TEXT
```

## 🆕 Nouvelles Fonctionnalités

### 1. **Système de Chat en Temps Réel**
- Conversations directes entre utilisateurs
- Messages avec statut de lecture
- Support mobile avec tokens
- Présence en ligne des utilisateurs

### 2. **Panier d'Achat**
- Gestion du panier avant commande
- Items de panier avec quantités
- Persistance entre sessions

### 3. **Codes Promo Avancés**
- Deux systèmes de codes promo (code_promo + promo_code)
- Suivi des utilisations
- Historique des réductions appliquées

### 4. **Système de Progression Patient**
- Suivi des points gagnés
- Cours et quiz complétés
- Récompenses et réductions

### 5. **Intégration Google Calendar**
- Synchronisation des rendez-vous
- Event ID pour chaque rendez-vous

### 6. **Système d'Approbation Admin**
- Workflow d'approbation pour:
  - Catégories de santé
  - Cours éducatifs
  - Questions de quiz
- Commentaires admin
- Historique des approbations

### 7. **Médias pour Cours Éducatifs**
- Upload de vidéos/images
- Intégration Cloudinary
- Métadonnées des médias

### 8. **Tickets pour Événements**
- Génération de codes tickets
- Envoi automatique par email
- Suivi des tickets envoyés

## 📊 Statistiques du Schéma Fusionné

| Catégorie | Nombre |
|-----------|--------|
| **Total Tables** | 40+ |
| **Tables Médicales** | 12 |
| **Tables Éducatives** | 8 |
| **Tables E-Commerce** | 10 |
| **Tables Chat** | 6 |
| **Tables Système** | 4 |
| **Clés Étrangères** | 50+ |
| **Index** | 80+ |

## 🚀 Installation

### Méthode 1: Import Direct
```bash
mysql -u root -p < mediconnect_COMPLETE_MERGED.sql
```

### Méthode 2: phpMyAdmin
1. Ouvrir phpMyAdmin
2. Créer une nouvelle base de données `mediconnect`
3. Importer `mediconnect_COMPLETE_MERGED.sql`

### Méthode 3: Symfony (Recommandé pour développement)
```bash
# Créer la base
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

## ⚠️ Notes Importantes

### Compatibilité
- ✅ MySQL 5.7+
- ✅ MariaDB 10.4+
- ✅ Support JSON natif requis

### Doublons Gérés
Certaines tables/colonnes existent en double avec des noms légèrement différents:
- `est_lu` et `lue` dans notification (les deux sont conservés)
- `code_promo` et `promo_code` (deux systèmes différents)
- `categorie` et `categorie_id` dans produit (les deux sont utiles)

### Encodage
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- Support complet des emojis et caractères spéciaux

## 🔧 Maintenance

### Vérifier l'Intégrité
```bash
php bin/console doctrine:schema:validate
```

### Sauvegarder
```bash
mysqldump -u root -p mediconnect > backup_$(date +%Y%m%d).sql
```

### Mettre à Jour
```bash
# Générer une migration
php bin/console make:migration

# Appliquer
php bin/console doctrine:migrations:migrate
```

## 📈 Optimisations Incluses

### Index Composites
- `(produit_id, utilisateur_id)` pour avis_produit
- `(conversation_id, created_at)` pour chat_message
- `(queue_name, available_at, delivered_at, id)` pour messenger_messages

### Index de Performance
- Index sur toutes les clés étrangères
- Index sur les colonnes de recherche fréquente
- Index sur les colonnes de tri (date_creation, etc.)

### Cascade DELETE
- Suppression automatique des données liées
- Protection contre les orphelins
- Intégrité référentielle garantie

## 🎯 Cas d'Usage

### Pour Développement
Utilisez `mediconnect_COMPLETE_MERGED.sql` pour avoir toutes les fonctionnalités

### Pour Production
1. Importez le schéma complet
2. Configurez les sauvegardes automatiques
3. Optimisez les index selon l'usage réel

### Pour Tests
1. Importez le schéma
2. Chargez `database_sample_data.sql`
3. Testez toutes les fonctionnalités

## 📞 Support

Pour toute question:
- Email: isra.zguir@ieee.org
- Repository: https://github.com/devloppeurtn/Esprit-PI_DEV-3A55-AU-MEDICONNECT

## 📄 Licence

Proprietary - Esprit School of Engineering - PIDEV 2025-2026
