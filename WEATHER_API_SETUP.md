# Configuration de l'API Météo OpenWeatherMap

## Pourquoi avez-vous besoin d'une clé API ?

La météo s'affiche actuellement avec des données simulées. Pour obtenir de vraies prévisions météo pour vos événements, vous devez obtenir une clé API gratuite d'OpenWeatherMap.

## Comment obtenir votre clé API gratuite (5 minutes)

### Étape 1: Créer un compte
1. Allez sur [https://home.openweathermap.org/users/sign_up](https://home.openweathermap.org/users/sign_up)
2. Remplissez le formulaire d'inscription:
   - Username (nom d'utilisateur)
   - Email
   - Password (mot de passe)
3. Acceptez les conditions d'utilisation
4. Cliquez sur "Create Account"

### Étape 2: Vérifier votre email
1. Ouvrez votre boîte email
2. Cherchez l'email de OpenWeatherMap
3. Cliquez sur le lien de vérification

### Étape 3: Obtenir votre clé API
1. Connectez-vous à votre compte OpenWeatherMap
2. Allez dans la section "API keys" (dans le menu de votre compte)
3. Vous verrez une clé API par défaut déjà créée
4. Copiez cette clé (elle ressemble à: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`)

### Étape 4: Configurer votre application
1. Ouvrez le fichier `.env.local` (créez-le s'il n'existe pas) dans votre projet MediConnect
2. Ajoutez votre clé API:
   ```
   OPENWEATHER_API_KEY=your_api_key_here_from_openweathermap
   ```
   
   **⚠️ IMPORTANT:** 
   - Ne mettez JAMAIS votre vraie clé dans `.env` (ce fichier est versionné sur Git)
   - Utilisez toujours `.env.local` pour vos secrets (ce fichier n'est PAS versionné)
   - Le fichier `.env` doit contenir uniquement des valeurs d'exemple

3. Exemple de clé (remplacez par la vôtre):
   ```
   OPENWEATHER_API_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
   ```
4. Sauvegardez le fichier `.env.local`

### Étape 5: Redémarrer le serveur
1. Arrêtez le serveur Symfony (Ctrl+C dans le terminal)
2. Redémarrez-le avec:
   ```bash
   symfony server:start
   ```
3. Rafraîchissez la page des événements

## C'est tout ! 🎉

Maintenant, la météo réelle s'affichera pour chaque événement basée sur:
- La date de l'événement
- La localisation de l'événement

## Plan gratuit OpenWeatherMap

Le plan gratuit vous donne:
- ✅ 1,000 appels API par jour
- ✅ Prévisions météo jusqu'à 5 jours
- ✅ Données météo actuelles
- ✅ Température, humidité, vitesse du vent
- ✅ Icônes météo

C'est largement suffisant pour votre application MediConnect!

## Besoin d'aide ?

Si vous rencontrez des problèmes:
1. Vérifiez que votre clé API est correctement copiée (pas d'espaces)
2. Attendez 10-15 minutes après la création du compte (activation de la clé)
3. Vérifiez que vous avez bien vérifié votre email

## Sécurité

⚠️ **Important**: Ne partagez jamais votre clé API publiquement (GitHub, etc.)
Le fichier `.env` est déjà dans `.gitignore` pour votre sécurité.
