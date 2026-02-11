# Processus de connexion – MediConnect

## Flux complet (étape par étape)

### 1. Utilisateur entre email et mot de passe
- **Fichier** : `templates/auth/login.html.twig`
- **Champs** : `_username` (email), `_password`
- **Action** : formulaire POST vers `{{ path('app_login') }}` = `/login`

---

### 2. Requête POST vers /login
- **Config** : `config/packages/security.yaml` ligne 23 : `check_path: app_login`
- **Effet** : Symfony Security intercepte la requête **avant** le contrôleur
- **Fichier Symfony** : `FormLoginAuthenticator` (vendor)

---

### 3. Chargement de l'utilisateur
- **Fichier** : `src/Security/UserProvider.php`
- **Méthode** : `loadUserByIdentifier(string $identifier)`
- **Rôle** : charge l’utilisateur par email en base
- **Problème** : lignes 31-34, si `statut !== ACTIF`, il lance `UserNotFoundException`  
  → les comptes SUSPENDU et BANNI sont bloqués ici, avant le UserChecker
- **Conséquence** : Symfony transforme `UserNotFoundException` en `BadCredentialsException`, d’où le message « Email ou mot de passe incorrect »

---

### 4. Vérification du mot de passe
- **Fichier Symfony** : `DaoAuthenticationProvider` (vendor)
- Appelé uniquement si l’utilisateur a bien été chargé (étape 3)

---

### 5. Vérification du statut (UserChecker)
- **Fichier** : `src/Security/UserChecker.php`
- **Méthode** : `checkPreAuth(UserInterface $user)`
- **Rôle** : vérifie SUSPENDU et BANNI, et lance `CustomUserMessageAccountStatusException` si nécessaire
- **Appelant** : `UserCheckerListener` (vendor) via l’événement `CheckPassportEvent`
- **Important** : cette méthode n’est jamais exécutée pour SUSPENDU/BANNI si le UserProvider les bloque en étape 3

---

### 6. En cas d’échec
- **Fichier** : `src/Security/LoginFailureHandler.php`
- **Méthode** : `onAuthenticationFailure(Request, AuthenticationException)`
- **Rôle** : si une `AccountStatusException` est présente (ou dans `getPrevious()`), ajoute le message personnalisé en flash
- **Problème** : pour SUSPENDU/BANNI, l’exception est `UserNotFoundException`, pas `AccountStatusException`, donc aucun message personnalisé n’est affiché

---

### 7. Redirection vers /login
- **Config** : `failure_path: app_login`

---

### 8. Affichage de la page de connexion
- **Fichier** : `src/Controller/AuthController.php`
- **Méthode** : `login(AuthenticationUtils)`
- Récupère `getLastAuthenticationError()` et ajoute un flash si l’erreur n’est pas une `AccountStatusException`

---

### 9. Rendu du template
- **Fichier** : `templates/auth/login.html.twig`
- Affiche les flashes et, à défaut, le message d’erreur brut

---

## Correction nécessaire

Le blocage des comptes SUSPENDU et BANNI doit se faire dans le **UserChecker**, pas dans le **UserProvider**.

- Supprimer le contrôle de statut dans `UserProvider::loadUserByIdentifier()`
- Garder la logique dans `UserChecker::checkPreAuth()` qui lance déjà `CustomUserMessageAccountStatusException` pour SUSPENDU et BANNI
