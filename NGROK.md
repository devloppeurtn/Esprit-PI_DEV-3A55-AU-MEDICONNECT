# Accéder à MediConnect depuis ton téléphone avec ngrok

## 1. Installer ngrok

### Option A : Téléchargement direct
- Va sur https://ngrok.com/download
- Télécharge et installe ngrok

### Option B : Avec npm (si Node.js installé)
```bash
npm install -g ngrok
```

## 2. Démarrer ton projet Symfony

Dans un premier terminal :
```bash
symfony serve
```
Ou : `php -S 127.0.0.1:8000 -t public`

(Le serveur tourne sur http://127.0.0.1:8000)

## 3. Lancer ngrok

Dans un **second terminal** :
```bash
ngrok http 8000
```

Tu verras s'afficher une URL du type :
```
Forwarding  https://xxxx-xx-xx-xx-xx.ngrok-free.app -> http://localhost:8000
```

## 4. Configurer l'URL dans ton projet

1. Copie l'URL ngrok (ex: `https://abc123.ngrok-free.app`)
2. Ouvre `.env` et ajoute/modifie :
   ```
   APP_URL=https://abc123.ngrok-free.app
   ```
3. Vide le cache : `php bin/console cache:clear`

## 5. Accéder depuis ton téléphone

- Ouvre ton navigateur mobile
- Va sur l'URL ngrok (ex: `https://abc123.ngrok-free.app`)
- **Important** : Accède toujours à l'app via cette URL (pas localhost) pour que les liens dans les emails (mot de passe oublié) fonctionnent sur ton téléphone

## Notes

- L'URL ngrok change à chaque redémarrage (version gratuite) → mets à jour `APP_URL` dans `.env`
- Garde les deux terminaux ouverts (Symfony + ngrok) pendant tes tests
- Avec ngrok gratuit, une page d'avertissement peut s'afficher la première fois → clique sur "Visit Site"
