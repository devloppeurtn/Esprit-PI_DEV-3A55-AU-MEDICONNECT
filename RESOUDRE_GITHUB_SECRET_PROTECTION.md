# 🔒 Résoudre le problème GitHub Secret Scanning Protection

## ❌ Problème rencontré

GitHub a détecté des clés API (secrets) dans votre code et bloque le push pour protéger vos données sensibles.

**Secrets détectés:**
- Clé API OpenWeatherMap dans `.env` (ligne 93 et 111)
- Clé API OpenWeatherMap dans `WEATHER_API_SETUP.md` (ligne 37)

## ⚠️ Pourquoi c'est important

Les clés API ne doivent JAMAIS être versionnées dans Git car:
- N'importe qui peut voir l'historique Git et voler vos clés
- Les clés peuvent être utilisées pour faire des requêtes à votre compte
- Cela peut entraîner des coûts ou des abus

## ✅ Solution en 3 étapes

### Étape 1: Supprimer les secrets des fichiers versionnés

#### 1.1 Modifier le fichier `.env`

Remplacez la vraie clé par un placeholder:

```env
###> openweathermap (météo) ###
# Clé API OpenWeatherMap pour afficher la météo des événements
# Obtenez votre clé gratuite sur: https://openweathermap.org/api
# IMPORTANT: Ne jamais commiter la vraie clé! Utilisez .env.local
OPENWEATHER_API_KEY=your_openweather_api_key_here
###< openweathermap ###
```

#### 1.2 Modifier le fichier `WEATHER_API_SETUP.md`

Remplacez la vraie clé par un exemple:

```markdown
# Configuration de la clé API
OPENWEATHER_API_KEY=your_api_key_here_from_openweathermap
```

#### 1.3 Créer un fichier `.env.local` (NON versionné)

Ce fichier contiendra vos vraies clés et ne sera JAMAIS envoyé sur GitHub:

```bash
# Dans le terminal
cd MediConnect-isramedi
echo "OPENWEATHER_API_KEY=3e321f9414eaedbfab34983bda77a66e" > .env.local
```

Ou créez manuellement le fichier `.env.local` avec:

```env
# Clés API réelles (ce fichier n'est PAS versionné)
OPENWEATHER_API_KEY=3e321f9414eaedbfab34983bda77a66e
GROQ_API_KEY=votre_vraie_cle_groq_si_vous_en_avez_une
```

### Étape 2: Vérifier que `.env.local` est dans `.gitignore`

Ouvrez le fichier `.gitignore` et vérifiez que cette ligne existe:

```
.env.local
```

Si elle n'existe pas, ajoutez-la!

### Étape 3: Nettoyer l'historique Git et repousser

#### Option A: Créer un nouveau commit (RECOMMANDÉ - Plus simple)

```bash
# 1. Modifier les fichiers .env et WEATHER_API_SETUP.md comme indiqué ci-dessus

# 2. Ajouter les modifications
git add .env WEATHER_API_SETUP.md

# 3. Créer un commit
git commit -m "security: remove API keys from versioned files"

# 4. Pousser vers GitHub
git push origin AzzaFinal
```

#### Option B: Réécrire l'historique (AVANCÉ - Nettoie complètement)

⚠️ **ATTENTION**: Cette méthode réécrit l'historique Git. À utiliser seulement si vous êtes seul sur le projet ou après coordination avec l'équipe.

```bash
# 1. Installer git-filter-repo (si pas déjà installé)
# Sur Windows avec pip:
pip install git-filter-repo

# 2. Sauvegarder votre travail
git branch backup-avant-nettoyage

# 3. Supprimer les secrets de l'historique
git filter-repo --invert-paths --path .env --force
git filter-repo --invert-paths --path WEATHER_API_SETUP.md --force

# 4. Recréer les fichiers sans secrets
# (Suivre les instructions de l'Étape 1)

# 5. Ajouter et commiter
git add .env WEATHER_API_SETUP.md .gitignore
git commit -m "security: remove API keys and add placeholder values"

# 6. Forcer le push (⚠️ ATTENTION: réécrit l'historique distant)
git push origin AzzaFinal --force
```

### Étape 4: Révoquer et régénérer vos clés API (IMPORTANT!)

Puisque vos clés ont été exposées dans Git, il est recommandé de les régénérer:

#### Pour OpenWeatherMap:
1. Allez sur https://home.openweathermap.org/api_keys
2. Supprimez l'ancienne clé `3e321f9414eaedbfab34983bda77a66e`
3. Créez une nouvelle clé
4. Mettez la nouvelle clé dans `.env.local` (PAS dans `.env`)

#### Pour Groq (si vous en avez une):
1. Allez sur https://console.groq.com/keys
2. Révoquez l'ancienne clé
3. Créez une nouvelle clé
4. Mettez la nouvelle clé dans `.env.local`

## 📋 Checklist finale

- [ ] `.env` contient uniquement des placeholders (pas de vraies clés)
- [ ] `WEATHER_API_SETUP.md` contient uniquement des exemples
- [ ] `.env.local` existe et contient les vraies clés
- [ ] `.env.local` est dans `.gitignore`
- [ ] Commit créé avec les modifications
- [ ] Push réussi vers GitHub
- [ ] Anciennes clés API révoquées et régénérées

## 🎯 Bonnes pratiques pour l'avenir

### ✅ À FAIRE:
- Toujours utiliser `.env.local` pour les secrets
- Commiter `.env` avec des valeurs d'exemple
- Ajouter `.env.local` dans `.gitignore`
- Documenter les variables nécessaires dans `.env`

### ❌ À NE JAMAIS FAIRE:
- Commiter des clés API dans `.env`
- Mettre des secrets dans des fichiers `.md` ou documentation
- Partager des clés API dans des messages de commit
- Ignorer les avertissements de GitHub Secret Scanning

## 🆘 En cas de problème

Si le push est toujours bloqué après avoir suivi ces étapes:

1. **Vérifiez que vous avez bien modifié TOUS les fichiers mentionnés**
   ```bash
   git diff HEAD~1
   ```

2. **Utilisez le lien fourni par GitHub pour autoriser temporairement**
   - GitHub vous donne un lien pour "allow the secret"
   - Utilisez-le UNIQUEMENT si vous êtes sûr d'avoir nettoyé les secrets

3. **Contactez l'administrateur du dépôt**
   - Il peut désactiver temporairement la protection
   - Mais c'est une mauvaise pratique!

## 📚 Ressources

- [GitHub Secret Scanning](https://docs.github.com/en/code-security/secret-scanning)
- [Push Protection](https://docs.github.com/en/code-security/secret-scanning/working-with-secret-scanning-and-push-protection)
- [Symfony Environment Variables](https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables)

---

**Date de création:** 2026-03-03  
**Projet:** MediConnect  
**Branche:** AzzaFinal
