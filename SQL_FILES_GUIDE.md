# 📁 Guide des Fichiers SQL - MediConnect

## 🎯 Fichier Principal Recommandé

### ⭐ **MEDICONNECT_FINAL_COMPLETE.sql**
**C'EST LE FICHIER À UTILISER !**

- ✅ **881 lignes** de code SQL
- ✅ **40+ tables** complètes
- ✅ **Fusion parfaite** des deux schémas (Doctrine + phpMyAdmin)
- ✅ **Toutes les contraintes** de clés étrangères
- ✅ **Tous les index** optimisés
- ✅ **Prêt à l'emploi** - Un seul fichier à importer

```bash
# Import direct
mysql -u root -p < MEDICONNECT_FINAL_COMPLETE.sql

# Ou via phpMyAdmin
# Importer directement ce fichier
```

---

## 📚 Autres Fichiers Disponibles

### 1. **database_schema.sql**
- Schéma original généré depuis Doctrine
- 27 tables de base
- Utile pour comprendre la structure Symfony

### 2. **database_sample_data.sql**
- Données d'exemple pour tester
- Utilisateurs, produits, commandes, etc.
- À importer APRÈS le schéma

```bash
mysql -u root -p mediconnect < database_sample_data.sql
```

### 3. **mediconnect_COMPLETE_MERGED.sql**
- Version alternative du fichier complet
- Même contenu que MEDICONNECT_FINAL_COMPLETE.sql

### 4. **mediconnect_complete_part1.sql**
- Partie 1: Tables médicales et éducatives
- Ne pas utiliser seul

### 5. **mediconnect_complete_part2.sql**
- Partie 2: Tables e-commerce et événements
- Ne pas utiliser seul

### 6. **mediconnect_complete_part3.sql**
- Partie 3: Tables chat, système et contraintes
- Ne pas utiliser seul

---

## 🚀 Installation Rapide

### Option 1: Fichier Unique (Recommandé)
```bash
# 1. Importer le schéma complet
mysql -u root -p < MEDICONNECT_FINAL_COMPLETE.sql

# 2. (Optionnel) Charger les données d'exemple
mysql -u root -p mediconnect < database_sample_data.sql
```

### Option 2: Via phpMyAdmin
1. Ouvrir phpMyAdmin
2. Cliquer sur "Importer"
3. Sélectionner `MEDICONNECT_FINAL_COMPLETE.sql`
4. Cliquer sur "Exécuter"
5. ✅ Terminé !

### Option 3: Via Symfony Doctrine
```bash
# Créer la base
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

---

## 📊 Contenu du Schéma Complet

### Tables Médicales (12)
- `utilisateur` - Utilisateurs (Patient, Médecin, Secrétaire, Admin)
- `dossier_medical` - Dossiers médicaux
- `rendez_vous` - Rendez-vous
- `consultation` - Consultations
- `ordonnance` - Ordonnances
- `rapport_medical` - Rapports médicaux
- `medicament_actuel` - Médicaments actuels
- `document_patient` - Documents uploadés
- `planning_medecin` - Planning des médecins
- `invitation` - Invitations médecin/secrétaire
- `notification` - Notifications
- `webauthn_credential` - Authentification biométrique

### Tables Éducatives (8)
- `categorie_sante` - Catégories de santé
- `cours_educatif` - Cours éducatifs
- `question_quiz` - Questions de quiz
- `progression_utilisateur` - Progression des utilisateurs
- `reponse_utilisateur` - Réponses aux quiz
- `patient_progress` - Progression patient
- `quiz_attempts` - Tentatives de quiz

### Tables E-Commerce (10)
- `categorie_produit` - Catégories de produits
- `produit` - Produits
- `commande_produit` - Commandes
- `ligne_commande` - Lignes de commande
- `avis_produit` - Avis clients
- `code_promo` - Codes promo (version 1)
- `promo_code` - Codes promo (version 2)
- `utilisation_code_promo` - Historique d'utilisation
- `panier` - Paniers
- `panier_item` - Items du panier

### Tables Événements (2)
- `evenement` - Événements
- `participant` - Participants

### Tables Chat (6)
- `chat_conversation` - Conversations
- `chat_message` - Messages
- `chat_participant` - Participants au chat
- `chat_mobile_session` - Sessions mobiles
- `chat_mobile_token` - Tokens mobiles
- `user_presence` - Présence en ligne

### Tables Système (4)
- `app_settings` - Paramètres application
- `messenger_messages` - Messages Symfony
- `doctrine_migration_versions` - Versions migrations

---

## 🔍 Vérification Post-Installation

### Vérifier les tables créées
```sql
USE mediconnect;
SHOW TABLES;
-- Devrait afficher 40+ tables
```

### Vérifier une table spécifique
```sql
DESCRIBE utilisateur;
-- Devrait afficher toutes les colonnes
```

### Compter les tables
```sql
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'mediconnect';
-- Devrait retourner 40+
```

---

## ⚠️ Résolution de Problèmes

### Erreur: "Table already exists"
```bash
# Supprimer la base existante
mysql -u root -p -e "DROP DATABASE IF EXISTS mediconnect;"

# Réimporter
mysql -u root -p < MEDICONNECT_FINAL_COMPLETE.sql
```

### Erreur: "Foreign key constraint fails"
```bash
# Le fichier MEDICONNECT_FINAL_COMPLETE.sql gère déjà l'ordre correct
# Si problème, désactiver temporairement les contraintes:
mysql -u root -p mediconnect -e "SET FOREIGN_KEY_CHECKS=0;"
# Puis réimporter
```

### Erreur: "Unknown database"
```bash
# Créer manuellement la base
mysql -u root -p -e "CREATE DATABASE mediconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Puis importer
mysql -u root -p mediconnect < MEDICONNECT_FINAL_COMPLETE.sql
```

---

## 📝 Notes Importantes

### Encodage
- ✅ UTF-8 (utf8mb4)
- ✅ Support des emojis
- ✅ Support des caractères spéciaux

### Compatibilité
- ✅ MySQL 5.7+
- ✅ MariaDB 10.4+
- ✅ Support JSON natif requis

### Sécurité
- ⚠️ Les mots de passe doivent être hashés avec bcrypt
- ⚠️ Ne jamais commiter les fichiers `.env` avec des vraies clés
- ⚠️ Utiliser `.env.local` pour les secrets

---

## 📞 Support

Pour toute question:
- **Email**: isra.zguir@ieee.org
- **Repository**: https://github.com/devloppeurtn/Esprit-PI_DEV-3A55-AU-MEDICONNECT

---

## 📄 Licence

Proprietary - Esprit School of Engineering - PIDEV 2025-2026

---

## 🎓 Résumé

| Fichier | Utilisation | Priorité |
|---------|-------------|----------|
| **MEDICONNECT_FINAL_COMPLETE.sql** | ⭐ **À UTILISER** | 🔥 Haute |
| database_sample_data.sql | Données de test | Moyenne |
| database_schema.sql | Référence Doctrine | Basse |
| Autres fichiers | Archives/Référence | Basse |

**Recommandation**: Utilisez uniquement `MEDICONNECT_FINAL_COMPLETE.sql` pour l'installation !
