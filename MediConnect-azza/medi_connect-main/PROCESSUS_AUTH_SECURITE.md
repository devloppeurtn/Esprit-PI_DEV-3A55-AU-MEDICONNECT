# Processus d'inscription, login et sécurité - MediConnect

## Important : Pas de JWT pour l'authentification utilisateur

**MediConnect n'utilise PAS JWT pour l'authentification.** L'authentification est basée sur des **sessions PHP** (cookies). Le seul JWT présent sert à **Mercure** (temps réel).

---

## 1. Processus d'inscription

### Fichiers impliqués
- `src/Controller/AuthController.php` → `signup()`
- `src/Form/SignupFormType.php`
- `templates/auth/signup.html.twig`

### Étapes

| Étape | Action | Détail |
|-------|--------|--------|
| 1 | Utilisateur visite `/signup` ou `/signup/{role}` | Route publique (access_control) |
| 2 | Affichage du formulaire | SignupFormType : email, nomComplet, password, confirmPassword + champs selon rôle |
| 3 | Soumission POST | Validation du formulaire |
| 4 | Vérification email | `EntityManager->findOneBy(['email' => ...])` – email déjà utilisé ? |
| 5 | Création de l'utilisateur | `createUserByRole()` → Patient, Medecin, Admin, etc. |
| 6 | Statut Admin | Si Admin → `StatutCompte::SUSPENDU` (validation manuelle) |
| 7 | Hashage du mot de passe | `UserPasswordHasherInterface->hashPassword()` (bcrypt/argon2) |
| 8 | Sauvegarde | `EntityManager->persist()` + `flush()` |
| 9 | Redirection | Vers `/login` avec message de succès |

### Sécurité à l'inscription
- Mot de passe **jamais stocké en clair** → hashé avec algorithme `auto`
- Vérification unicité de l'email

---

## 2. Processus de connexion (login)

### Fichiers impliqués
- `config/packages/security.yaml` → `form_login`
- `src/Controller/AuthController.php` → `login()` (GET seulement pour afficher le formulaire)
- `src/Security/UserProvider.php`
- `src/Security/UserChecker.php`
- `src/Security/LoginFailureHandler.php`
- `templates/auth/login.html.twig`

### Flux détaillé

```
┌─────────────────────────────────────────────────────────────────────────┐
│ 1. Utilisateur envoie POST /login (_username, _password, _csrf_token)   │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ 2. Symfony Security intercepte (FormLoginAuthenticator)                 │
│    - check_path: app_login → traite la soumission                       │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ 3. UserProvider::loadUserByIdentifier(email)                            │
│    - Charge l'utilisateur depuis la BDD                                 │
│    - Si non trouvé → UserNotFoundException → BadCredentialsException    │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ 4. Vérification du mot de passe                                         │
│    - DaoAuthenticationProvider compare le hash                          │
│    - Si invalide → BadCredentialsException                              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ 5. UserChecker::checkPreAuth(user)                                      │
│    - Si SUSPENDU → CustomUserMessageAccountStatusException              │
│    - Si BANNI → CustomUserMessageAccountStatusException                 │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
           ✅ Succès                        ❌ Échec
                    │                               │
                    ▼                               ▼
┌──────────────────────────────┐  ┌──────────────────────────────────────┐
│ AdminLoginSuccessHandler     │  │ LoginFailureHandler                   │
│ - Crée la session            │  │ - Ajoute flash message (suspendu/     │
│ - Redirige vers profil       │  │   banni ou mauvais identifiants)      │
│   ou admin si ROLE_ADMIN     │  │ - Redirige vers /login                │
└──────────────────────────────┘  └──────────────────────────────────────┘
```

---

## 3. Où est stockée l'authentification ? (Session, pas JWT)

### Mécanisme : Session PHP

| Élément | Stockage | Détail |
|---------|----------|--------|
| **Token de session** | Cookie côté navigateur | Nom : `PHPSESSID` (ou `MOCKSESSID` en dev) |
| **Données de session** | Serveur (fichiers par défaut) | Dossier `var/sessions/` ou handler configuré dans `framework.yaml` |
| **Utilisateur authentifié** | Dans la session serveur | Objet `Utilisateur` sérialisé sous la clé `_security_main` |

### Configuration (framework.yaml)

```yaml
session:
    handler_id: null    # = fichiers (var/sessions/)
    cookie_secure: auto
    cookie_samesite: lax
```

- **handler_id: null** → sessions stockées dans `var/sessions/`
- Le cookie contient uniquement l'**ID de session**, pas les données utilisateur
- Les données sensibles restent côté serveur

### Remember Me (optionnel)

```yaml
remember_me:
    secret: '%kernel.secret%'
    lifetime: 604800   # 1 semaine
```

- Cookie `REMEMBERME` stocké côté navigateur
- Permet de rester connecté 7 jours sans ouvrir de session active

---

## 4. JWT dans MediConnect

### Utilisation réelle du JWT

| Contexte | Rôle | Stockage |
|----------|------|----------|
| **Mercure** | Signer les JWT pour publier/souscrire aux événements temps réel | Clé secrète dans `.env` : `MERCURE_JWT_SECRET` |
| **Authentification utilisateur** | Aucun | Pas de Lexik JWT, pas de token JWT pour les utilisateurs |

### Où est la clé JWT ?

- **Fichier** : `.env`
- **Variable** : `MERCURE_JWT_SECRET="!ChangeThisMercureHubJWTSecretKey!"`
- **Usage** : Mercure génère des JWT signés pour autoriser la publication/souscription aux topics

Le JWT n’est pas stocké pour l’utilisateur : il est généré à la volée par Mercure pour ses requêtes.

---

## 5. Sécurité globale

| Mécanisme | Implémentation |
|-----------|----------------|
| **Mots de passe** | Hashage avec `auto` (bcrypt/argon2), jamais en clair |
| **CSRF** | Token `_csrf_token` dans le formulaire login (Twig : `csrf_token('authenticate')`) |
| **Sessions** | ID en cookie, données en fichiers serveur |
| **Contrôle d'accès** | access_control par rôles (ROLE_ADMIN, ROLE_USER, etc.) |
| **Comptes suspendus/bannis** | UserChecker avant création du token d’authentification |
| **Remember Me** | Cookie sécurisé avec secret |

---

## 6. Résumé

| Question | Réponse |
|----------|---------|
| **Authentification utilisateur** | Sessions PHP (cookie + session serveur) |
| **JWT pour les utilisateurs** | Non utilisé |
| **JWT Mercure** | Oui, pour le temps réel ; clé dans `.env` |
| **Stockage session** | Fichiers dans `var/sessions/` |
| **Cookie session** | `PHPSESSID` (ID uniquement) |
