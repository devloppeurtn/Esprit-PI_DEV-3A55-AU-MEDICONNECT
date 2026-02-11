# Instructions pour l'authentification MediConnect

## Configuration de la base de données MySQL

1. **Créer la base de données** dans phpMyAdmin ou via MySQL :
```sql
CREATE DATABASE mediconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Configurer le fichier `.env`** :
   - Le fichier `.env` est déjà configuré pour MySQL avec WAMP
   - Modifiez les identifiants si nécessaire :
   ```
   DATABASE_URL="mysql://root:@127.0.0.1:3306/mediconnect?serverVersion=8.0.32&charset=utf8mb4"
   ```

## Installation des dépendances

1. **Installer JWT (si la connexion réseau fonctionne)** :
```bash
composer require lexik/jwt-authentication-bundle
```

Si vous avez des problèmes de connexion réseau, vous pouvez :
- Configurer un proxy dans composer
- Ou installer manuellement depuis un autre réseau
- Ou utiliser l'authentification Symfony standard (déjà configurée)

## Création des migrations

1. **Créer les migrations** :
```bash
php bin/console make:migration
```

2. **Exécuter les migrations** :
```bash
php bin/console doctrine:migrations:migrate
```

## Configuration de la sécurité

La sécurité est déjà configurée dans `config/packages/security.yaml` avec :
- UserProvider personnalisé
- Form login
- Logout
- Contrôle d'accès par rôle

## Routes disponibles

- `/login` - Page de connexion
- `/signup` - Page d'inscription (sélection du rôle)
- `/signup/PATIENT` - Inscription Patient
- `/signup/MEDECIN` - Inscription Médecin
- `/signup/SECRETAIRE` - Inscription Secrétaire
- `/logout` - Déconnexion

## Rôles et contrôle d'accès

Les routes sont protégées selon les rôles :
- `/admin/*` - Requiert ROLE_ADMIN
- `/medecin/*` - Requiert ROLE_MEDECIN
- `/patient/*` - Requiert ROLE_PATIENT
- `/secretaire/*` - Requiert ROLE_SECRETAIRE

## Structure des entités

### Utilisateur (classe parente)
- id
- email
- motDePasseHash
- nomComplet
- role (RoleUtilisateur enum)
- statut (StatutCompte enum)
- dateCreation
- derniereConnexion

### Patient (hérite de Utilisateur)
- telephone
- dateNaissance
- adresse

### Medecin (hérite de Utilisateur)
- specialite
- adresseCabinet
- numeroLicence

### Secretaire (hérite de Utilisateur)
- telephone

### Admin (hérite de Utilisateur)
- Aucun attribut supplémentaire

## Enums

### RoleUtilisateur
- ADMIN
- PATIENT
- MEDECIN
- SECRETAIRE

### StatutCompte
- ACTIF
- BANNI
- SUSPENDU

## Notes importantes

1. **JWT** : Pour une authentification JWT complète, vous devrez installer `lexik/jwt-authentication-bundle` et générer les clés :
```bash
php bin/console lexik:jwt:generate-keypair
```

2. **Statut du compte** : Par défaut, tous les nouveaux utilisateurs ont le statut ACTIF. Vous pouvez modifier cela dans le constructeur de `Utilisateur`.

3. **Validation** : Les formulaires incluent une validation de base. Vous pouvez ajouter plus de contraintes dans les entités si nécessaire.

4. **Sécurité** : Les mots de passe sont automatiquement hashés avec l'algorithme configuré dans `security.yaml`.

## Test de l'authentification

1. Accédez à `/signup/PATIENT` pour créer un compte patient
2. Remplissez le formulaire
3. Connectez-vous avec `/login`
4. Vous serez redirigé vers `/home` après connexion

## Personnalisation

- Les couleurs sont définies dans `public/assets/css/auth.css` avec les variables CSS
- Les templates sont dans `templates/auth/`
- Les formulaires sont dans `src/Form/`
- Le contrôleur est dans `src/Controller/AuthController.php`
