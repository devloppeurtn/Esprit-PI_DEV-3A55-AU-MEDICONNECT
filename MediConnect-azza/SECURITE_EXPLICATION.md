# 🔐 Explication de la Sécurité - MediConnect

## Vue d'ensemble

Ce projet utilise le système de sécurité intégré de Symfony pour gérer l'authentification et l'autorisation. Voici une explication détaillée de chaque composant.

---

## 1. Configuration de Sécurité (`security.yaml`)

### 1.1 Hashage des Mots de Passe

```yaml
password_hashers:
    Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
    App\Entity\Utilisateur: 'auto'
```

**Explication :**
- Les mots de passe sont automatiquement hashés avec l'algorithme le plus sécurisé disponible (bcrypt ou argon2i)
- **Jamais stockés en clair** dans la base de données
- Le hashage est automatique lors de l'inscription et de la connexion

**Sécurité :** ✅ Protection contre les fuites de données (même si la base est compromise, les mots de passe restent protégés)

---

### 1.2 Fournisseur d'Utilisateurs (User Provider)

```yaml
providers:
    app_user_provider:
        id: App\Security\UserProvider
```

**Explication :**
- Utilise un **UserProvider personnalisé** (`App\Security\UserProvider`)
- Ce provider charge les utilisateurs depuis la base de données Doctrine
- Vérifie le statut du compte (ACTIF/BANNI/SUSPENDU)

**Sécurité :** ✅ Contrôle centralisé de l'accès utilisateur

---

### 1.3 Firewalls (Pare-feu)

Les firewalls définissent **quelles routes sont protégées** et lesquelles sont publiques :

#### Firewall `dev` (Développement)
```yaml
dev:
    pattern: ^/(_(profiler|wdt)|css|images|js|assets)/
    security: false
```
- Routes publiques pour les assets (CSS, JS, images)
- Pas d'authentification requise

#### Firewall `login` et `signup`
```yaml
login:
    pattern: ^/login
    security: false

signup:
    pattern: ^/signup
    security: false
```
- Routes publiques pour l'inscription et la connexion
- Permet aux utilisateurs non connectés d'accéder à ces pages

#### Firewall `main` (Principal)
```yaml
main:
    lazy: true
    provider: app_user_provider
    form_login:
        login_path: app_login
        check_path: app_login
        default_target_path: app_home
        username_parameter: _username
        password_parameter: _password
    logout:
        path: app_logout
        target: app_home
    remember_me:
        secret: '%kernel.secret%'
        lifetime: 604800 # 1 week
```

**Explication :**
- **`lazy: true`** : Charge les utilisateurs uniquement quand nécessaire (performance)
- **`form_login`** : Authentification par formulaire
  - `login_path` : Route pour afficher le formulaire de connexion
  - `check_path` : Route qui traite la soumission du formulaire
  - `default_target_path` : Redirection après connexion réussie
- **`logout`** : Gestion de la déconnexion
- **`remember_me`** : Cookie "Se souvenir de moi" valide 1 semaine

**Sécurité :** ✅ Protection CSRF intégrée, gestion sécurisée des sessions

---

### 1.4 Contrôle d'Accès (Access Control)

```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/medecin, roles: ROLE_MEDECIN }
    - { path: ^/patient, roles: ROLE_PATIENT }
    - { path: ^/secretaire, roles: ROLE_SECRETAIRE }
```

**Explication :**
- **Contrôle basé sur les rôles** (RBAC - Role-Based Access Control)
- Seuls les utilisateurs avec le bon rôle peuvent accéder aux routes correspondantes
- Si un utilisateur non autorisé tente d'accéder, Symfony redirige vers la page de connexion

**Sécurité :** ✅ Protection contre l'accès non autorisé aux zones sensibles

---

## 2. UserProvider Personnalisé (`UserProvider.php`)

### 2.1 Chargement d'Utilisateur

```php
public function loadUserByIdentifier(string $identifier): UserInterface
{
    $user = $this->entityManager->getRepository(Utilisateur::class)
        ->findOneBy(['email' => $identifier]);

    if (!$user) {
        throw new UserNotFoundException(...);
    }

    // Vérifier le statut du compte
    if ($user->getStatut() !== StatutCompte::ACTIF) {
        throw new UserNotFoundException('Votre compte n\'est pas actif.');
    }

    return $user;
}
```

**Sécurité :**
- ✅ Vérifie que l'utilisateur existe
- ✅ **Blocage des comptes BANNI ou SUSPENDU** même avec les bons identifiants
- ✅ Protection contre les comptes désactivés

---

### 2.2 Rafraîchissement d'Utilisateur

```php
public function refreshUser(UserInterface $user): UserInterface
{
    if (!$user instanceof Utilisateur) {
        throw new UnsupportedUserException(...);
    }
    return $this->loadUserByIdentifier($user->getUserIdentifier());
}
```

**Explication :**
- Recharge l'utilisateur depuis la base de données
- Permet de vérifier les changements de statut en temps réel

**Sécurité :** ✅ Les changements de statut sont pris en compte immédiatement

---

### 2.3 Mise à Jour du Mot de Passe

```php
public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
{
    $user->setPassword($newHashedPassword);
    $this->entityManager->flush();
}
```

**Explication :**
- Permet de mettre à jour le hash du mot de passe si l'algorithme change
- Utile pour migrer vers des algorithmes plus sécurisés

---

## 3. Contrôleur d'Authentification (`AuthController.php`)

### 3.1 Connexion (Login)

```php
public function login(AuthenticationUtils $authenticationUtils): Response
{
    if ($this->getUser()) {
        return $this->redirectToRoute('app_home');
    }
    // ...
}
```

**Sécurité :**
- ✅ Redirige les utilisateurs déjà connectés (évite les doubles connexions)
- ✅ Utilise `AuthenticationUtils` pour gérer les erreurs de manière sécurisée
- ✅ Protection CSRF intégrée via Symfony

---

### 3.2 Inscription (Signup)

#### Vérification de l'Email Unique

```php
$existingUser = $this->entityManager->getRepository(Utilisateur::class)
    ->findOneBy(['email' => $data['email']]);

if ($existingUser) {
    $this->addFlash('error', 'Cet email est déjà utilisé.');
    return $this->render(...);
}
```

**Sécurité :**
- ✅ Empêche la création de comptes en double
- ✅ Protection contre les inscriptions multiples

#### Vérification de la Confirmation du Mot de Passe

```php
if ($data['password'] !== $confirmPassword) {
    $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
    return $this->render(...);
}
```

**Sécurité :**
- ✅ Vérification côté serveur (ne pas faire confiance au client)
- ✅ Empêche les erreurs de saisie

#### Hashage du Mot de Passe

```php
$hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
$user->setPassword($hashedPassword);
```

**Sécurité :**
- ✅ Le mot de passe est hashé **avant** d'être stocké en base
- ✅ Utilise l'algorithme configuré dans `security.yaml`

---

### 3.3 Déconnexion (Logout)

```php
public function logout(): void
{
    throw new \LogicException('Cette méthode peut être vide...');
}
```

**Explication :**
- La méthode est interceptée par Symfony
- Symfony invalide la session et le cookie "remember me"
- Redirige vers `app_home` (configuré dans `security.yaml`)

**Sécurité :** ✅ Déconnexion complète et sécurisée

---

## 4. Entité Utilisateur (`Utilisateur.php`)

### 4.1 Implémentation des Interfaces de Sécurité

```php
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
```

**Explication :**
- `UserInterface` : Interface requise par Symfony Security
- `PasswordAuthenticatedUserInterface` : Interface pour le hashage des mots de passe

---

### 4.2 Méthodes de Sécurité

#### `getUserIdentifier()`
```php
public function getUserIdentifier(): string
{
    return (string) $this->email;
}
```
- Retourne l'identifiant unique (email)

#### `getRoles()`
```php
public function getRoles(): array
{
    return ['ROLE_' . $this->role->value];
}
```
- Retourne les rôles de l'utilisateur
- Format : `ROLE_ADMIN`, `ROLE_PATIENT`, `ROLE_MEDECIN`, `ROLE_SECRETAIRE`

#### `getPassword()`
```php
public function getPassword(): string
{
    return $this->motDePasseHash;
}
```
- Retourne le hash du mot de passe (jamais le mot de passe en clair)

#### `eraseCredentials()`
```php
public function eraseCredentials(): void
{
    // Si vous stockez des données sensibles temporaires, effacez-les ici
}
```
- Méthode appelée après authentification pour effacer les données sensibles

---

## 5. Protection CSRF (Cross-Site Request Forgery)

### Protection Automatique

Symfony protège automatiquement les formulaires contre les attaques CSRF :

```twig
<input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
```

**Sécurité :**
- ✅ Chaque formulaire a un token unique
- ✅ Le token est vérifié côté serveur
- ✅ Protection contre les requêtes malveillantes depuis d'autres sites

---

## 6. Protection XSS (Cross-Site Scripting)

### Échappement Automatique dans Twig

```twig
{{ error.messageKey|trans(error.messageData, 'security') }}
```

**Sécurité :**
- ✅ Twig échappe automatiquement toutes les variables
- ✅ Protection contre l'injection de code JavaScript malveillant

---

## 7. Gestion des Sessions

### Configuration Symfony

- Les sessions sont gérées automatiquement par Symfony
- Stockage sécurisé des données de session
- Expiration automatique après inactivité

**Sécurité :** ✅ Protection contre le vol de session

---

## 8. Points de Sécurité Importants

### ✅ Ce qui est Sécurisé

1. **Mots de passe hashés** : Jamais stockés en clair
2. **Vérification du statut** : Comptes BANNI/SUSPENDU bloqués
3. **Protection CSRF** : Tokens sur tous les formulaires
4. **Protection XSS** : Échappement automatique dans Twig
5. **Contrôle d'accès** : Routes protégées par rôle
6. **Validation des données** : Vérification côté serveur
7. **Sessions sécurisées** : Gestion automatique par Symfony

### ⚠️ Points à Améliorer (Recommandations)

1. **Rate Limiting** : Limiter les tentatives de connexion
   ```php
   // À implémenter : Limiter à 5 tentatives par IP
   ```

2. **Validation Email** : Vérifier que l'email est valide et vérifié
   ```php
   // À implémenter : Envoi d'email de confirmation
   ```

3. **Mot de Passe Fort** : Exiger une complexité minimale
   ```php
   // Dans SignupFormType, ajouter des contraintes :
   new Length(['min' => 8]),
   new Regex(['pattern' => '/[A-Z]/']), // Majuscule
   new Regex(['pattern' => '/[a-z]/']), // Minuscule
   new Regex(['pattern' => '/[0-9]/']), // Chiffre
   ```

4. **Logs de Sécurité** : Enregistrer les tentatives de connexion échouées
   ```php
   // À implémenter : Logger les échecs d'authentification
   ```

5. **HTTPS** : Forcer HTTPS en production
   ```yaml
   # Dans security.yaml
   access_control:
       - { path: ^/, requires_channel: https }
   ```

6. **JWT (JSON Web Tokens)** : Pour les API REST
   - Déjà mentionné dans `INSTRUCTIONS_AUTH.md`
   - À installer quand le réseau le permet

---

## 9. Résumé de la Sécurité

| Composant | Statut | Description |
|-----------|--------|-------------|
| Hashage MDP | ✅ | Bcrypt/Argon2 automatique |
| User Provider | ✅ | Vérification statut compte |
| CSRF | ✅ | Protection automatique |
| XSS | ✅ | Échappement Twig |
| Contrôle Accès | ✅ | Basé sur les rôles |
| Sessions | ✅ | Gestion Symfony |
| Rate Limiting | ⚠️ | À implémenter |
| Validation Email | ⚠️ | À implémenter |
| HTTPS | ⚠️ | À configurer en prod |

---

## 10. Comment Tester la Sécurité

### Test 1 : Tentative de Connexion avec Mauvais Mot de Passe
```
1. Aller sur /login
2. Entrer un email valide avec un mauvais mot de passe
3. ✅ Doit afficher une erreur
```

### Test 2 : Accès Non Autorisé
```
1. Se connecter en tant que PATIENT
2. Essayer d'accéder à /admin
3. ✅ Doit rediriger vers /login
```

### Test 3 : Compte Banni
```
1. Créer un compte et le bannir (statut = BANNI)
2. Essayer de se connecter
3. ✅ Doit afficher "Votre compte n'est pas actif"
```

### Test 4 : Protection CSRF
```
1. Créer un formulaire malveillant sans token CSRF
2. Essayer de soumettre
3. ✅ Doit être rejeté par Symfony
```

---

## Conclusion

Le système de sécurité actuel est **solide** pour une application Symfony standard. Les points critiques (hashage, CSRF, contrôle d'accès) sont bien implémentés. Les améliorations suggérées (rate limiting, validation email, HTTPS) sont des bonnes pratiques pour une application en production.
