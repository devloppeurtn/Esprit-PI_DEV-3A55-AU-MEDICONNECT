# MediConnect - Documentation Base de Données

## 📋 Fichiers SQL Disponibles

### 1. `database_schema.sql`
Fichier principal contenant la structure complète de la base de données avec:
- Toutes les tables (27 tables)
- Tous les index
- Toutes les contraintes de clés étrangères
- Commentaires détaillés pour chaque table

### 2. `database_sample_data.sql`
Fichier contenant des données d'exemple pour tester l'application:
- Utilisateurs (Admin, Médecins, Patients, Secrétaires)
- Dossiers médicaux
- Rendez-vous
- Produits et catégories
- Commandes
- Événements
- Notifications

## 🚀 Installation de la Base de Données

### Méthode 1: Ligne de Commande MySQL

```bash
# Se connecter à MySQL
mysql -u root -p

# Exécuter le schéma
mysql -u root -p < database_schema.sql

# (Optionnel) Charger les données d'exemple
mysql -u root -p mediconnect < database_sample_data.sql
```

### Méthode 2: phpMyAdmin

1. Ouvrir phpMyAdmin
2. Cliquer sur "Importer"
3. Sélectionner `database_schema.sql`
4. Cliquer sur "Exécuter"
5. (Optionnel) Répéter avec `database_sample_data.sql`

### Méthode 3: Symfony Doctrine (Recommandé)

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# (Optionnel) Charger les fixtures si disponibles
php bin/console doctrine:fixtures:load
```

## 📊 Structure de la Base de Données

### Tables Principales

#### 👤 Gestion des Utilisateurs
- **utilisateur** - Table principale (héritage: Patient, Médecin, Secrétaire, Admin, Organisateur)
- **webauthn_credential** - Authentification biométrique
- **invitation** - Invitations médecin → secrétaire

#### 🏥 Gestion Médicale
- **dossier_medical** - Dossiers patients
- **rendez_vous** - Rendez-vous médecin/patient
- **consultation** - Consultations médicales
- **ordonnance** - Ordonnances
- **rapport_medical** - Rapports médicaux
- **medicament_actuel** - Médicaments du patient
- **document_patient** - Documents uploadés
- **planning_medecin** - Disponibilités médecins

#### 📚 Contenu Éducatif
- **categorie_sante** - Catégories de santé
- **cours_educatif** - Cours éducatifs
- **question_quiz** - Questions de quiz
- **progression_utilisateur** - Progression des utilisateurs
- **reponse_utilisateur** - Réponses aux quiz

#### 🛒 E-Commerce
- **categorie_produit** - Catégories de produits
- **produit** - Produits médicaux
- **commande_produit** - Commandes
- **ligne_commande** - Détails des commandes
- **avis_produit** - Avis clients
- **promo_code** - Codes promotionnels

#### 📅 Événements
- **evenement** - Événements médicaux
- **participant** - Participants aux événements

#### 🔔 Système
- **notification** - Notifications utilisateurs
- **messenger_messages** - Messages asynchrones Symfony

## 🔑 Relations Importantes

### Utilisateur (Héritage)
```
utilisateur (table principale)
├── Patient (discr = 'patient')
├── Medecin (discr = 'medecin')
├── Secretaire (discr = 'secretaire')
├── Admin (discr = 'admin')
└── Organisateur (discr = 'organisateur')
```

### Flux Médical
```
Patient → Rendez-vous → Consultation → Ordonnance
                                    → Rapport Médical
```

### Flux E-Commerce
```
Utilisateur → Commande → Ligne Commande → Produit
```

## 📈 Statistiques

- **Total Tables**: 27
- **Total Relations**: 35+ clés étrangères
- **Types d'Index**: PRIMARY, UNIQUE, INDEX
- **Encodage**: UTF-8 (utf8mb4)
- **Moteur**: InnoDB (par défaut)

## 🔒 Sécurité

### Mots de Passe
Les mots de passe sont hashés avec **bcrypt** (Symfony):
```php
// Exemple de hashage
$hashedPassword = password_hash('password123', PASSWORD_BCRYPT);
```

### Données Sensibles
- Les mots de passe ne sont JAMAIS stockés en clair
- Les tokens de réinitialisation expirent automatiquement
- Les credentials biométriques sont chiffrés

## 🛠️ Maintenance

### Sauvegarder la Base de Données
```bash
mysqldump -u root -p mediconnect > backup_$(date +%Y%m%d).sql
```

### Restaurer une Sauvegarde
```bash
mysql -u root -p mediconnect < backup_20260507.sql
```

### Vérifier l'Intégrité
```bash
php bin/console doctrine:schema:validate
```

### Mettre à Jour le Schéma
```bash
# Générer une nouvelle migration
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate
```

## 📝 Notes Importantes

1. **UUID vs INT**: Certaines tables utilisent des UUID (BINARY(16)) pour les IDs
2. **JSON Fields**: Plusieurs champs utilisent le type JSON (MySQL 5.7+)
3. **Cascade Delete**: Attention aux suppressions en cascade configurées
4. **Timestamps**: Tous les timestamps sont en UTC

## 🐛 Dépannage

### Erreur: "Table already exists"
```bash
# Supprimer et recréer
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Erreur: "Foreign key constraint fails"
```bash
# Vérifier l'ordre d'insertion des données
# Les tables parentes doivent être remplies avant les tables enfants
```

### Erreur: "Unknown column"
```bash
# Mettre à jour le schéma
php bin/console doctrine:schema:update --force
```

## 📞 Support

Pour toute question concernant la base de données:
- Email: isra.zguir@ieee.org
- Repository: https://github.com/devloppeurtn/Esprit-PI_DEV-3A55-AU-MEDICONNECT

## 📄 Licence

Proprietary - Esprit School of Engineering
