# 🤖 Configuration de l'IA Groq pour les Recommandations Personnalisées

## 📋 Vue d'ensemble

Le système de recommandations utilise l'API Groq avec le modèle **Llama 3.3 70B Versatile** pour générer des suggestions d'événements intelligentes et contextualisées basées sur le dossier médical du patient.

### Avantages de Groq + Llama

✅ **Ultra-rapide** : Groq offre les inférences les plus rapides du marché  
✅ **Gratuit** : API gratuite avec limite généreuse  
✅ **Intelligent** : Llama 3.3 70B comprend le contexte médical  
✅ **Sécurisé** : Données anonymisées avant envoi  
✅ **Fallback** : Système de mots-clés si l'API n'est pas disponible

## 🚀 Configuration Rapide (5 minutes)

### Étape 1 : Obtenir une Clé API Groq

1. **Créer un compte** : https://console.groq.com/
2. **Aller dans "API Keys"** : https://console.groq.com/keys
3. **Créer une nouvelle clé** : Cliquez sur "Create API Key"
4. **Copier la clé** : Elle ressemble à `gsk_...`

### Étape 2 : Configurer la Clé dans .env

Ouvrez le fichier `.env` et remplacez :

```env
GROQ_API_KEY=your_groq_api_key_here
```

Par votre vraie clé :

```env
GROQ_API_KEY=gsk_votre_cle_ici
```

### Étape 3 : Vider le Cache

```bash
php bin/console cache:clear
```

### Étape 4 : Tester

1. Connectez-vous avec le patient de test :
   - Email : `patient.test@mediconnect.com`
   - Mot de passe : `test123`

2. Allez sur : http://127.0.0.1:8000/evenement

3. Vous devriez voir le badge **"Propulsé par Groq Llama"** 🎉

## 🏗️ Architecture Technique

### Flux de Données

```
Patient → Profil Médical Anonymisé → API Groq → Recommandations IA → Affichage
                                          ↓
                                    (si erreur)
                                          ↓
                                  Système Fallback
```

### Données Envoyées à Groq (Anonymisées)

```json
{
  "age": 48,
  "gender": "M",
  "chronic_diseases": "Diabète de type 2, Hypertension",
  "allergies": "Pénicilline",
  "recent_consultations": ["Contrôle glycémie", "Suivi tension"],
  "medications": ["Metformine", "Ramipril"]
}
```

**Note** : Aucune information personnelle identifiable (nom, email, etc.) n'est envoyée.

### Modèle Utilisé

- **Nom** : `llama-3.3-70b-versatile`
- **Paramètres** :
  - Temperature : 0.7 (équilibre créativité/précision)
  - Max tokens : 500
  - Format : JSON structuré

## 📊 Exemple de Réponse IA

### Prompt Envoyé

```
Analyse ce profil médical anonymisé et recommande les 3 événements 
les plus pertinents parmi la liste fournie.

PROFIL PATIENT (anonymisé):
Âge: 48 ans
Maladies chroniques: Diabète de type 2, Hypertension artérielle
Allergies: Pénicilline, Pollen

ÉVÉNEMENTS DISPONIBLES:
1. [ID: abc-123] Atelier Gestion du Diabète et Nutrition
   Date: 2026-03-15
   Description: Apprenez à gérer votre diabète au quotidien...

2. [ID: def-456] Conférence Prévention Cardiovasculaire
   Date: 2026-03-20
   Description: Prévention des maladies du cœur...
```

### Réponse IA

```json
{
  "recommendations": [
    {
      "event_id": "abc-123",
      "score": 95,
      "reasons": [
        "Correspond directement à votre diabète de type 2",
        "Inclut des conseils nutritionnels adaptés",
        "Atelier pratique avec suivi personnalisé"
      ]
    },
    {
      "event_id": "def-456",
      "score": 85,
      "reasons": [
        "Important pour votre hypertension artérielle",
        "Prévention des complications cardiovasculaires",
        "Recommandé pour les patients diabétiques"
      ]
    }
  ]
}
```

## 🔧 Fichiers Modifiés/Créés

### Nouveaux Fichiers

1. **`src/Service/GroqAIService.php`**
   - Service principal d'intégration Groq
   - Gestion des appels API
   - Parsing des réponses JSON
   - Système de fallback

2. **`GROQ_AI_SETUP.md`** (ce fichier)
   - Documentation complète
   - Guide de configuration
   - Exemples d'utilisation

### Fichiers Modifiés

1. **`src/Service/EventRecommendationService.php`**
   - Intégration du GroqAIService
   - Extraction du profil médical enrichi (âge, genre)
   - Fallback automatique

2. **`.env`**
   - Ajout de `GROQ_API_KEY`

3. **`config/services.yaml`**
   - Configuration du service Groq
   - Injection de la clé API

4. **`templates/evenement/index.html.twig`**
   - Badge "Propulsé par Groq Llama"
   - Indicateur du mode (IA vs Basique)

## 💡 Fonctionnalités Avancées

### 1. Prise en Compte de l'Âge

L'IA adapte ses recommandations selon l'âge :
- **< 30 ans** : Prévention, sport, nutrition
- **30-60 ans** : Gestion des maladies chroniques
- **> 60 ans** : Suivi, dépistage, maintien autonomie

### 2. Recommandations Genrées

Si vous ajoutez un champ `genre` dans l'entité Patient :

```php
// Dans Patient.php
#[ORM\Column(type: Types::STRING, length: 1, nullable: true)]
private ?string $genre = null; // 'M' ou 'F'
```

L'IA pourra recommander :
- **Femmes** : Octobre Rose, dépistage cancer du sein, ostéoporose
- **Hommes** : Movember, cancer de la prostate, santé cardiaque

### 3. Analyse des Médicaments

L'IA analyse les médicaments actuels pour :
- Identifier les pathologies sous-jacentes
- Suggérer des événements de suivi
- Recommander des ateliers d'observance

## 🔒 Sécurité et Confidentialité

### Données Anonymisées

✅ **Envoyé à Groq** :
- Âge (nombre)
- Genre (M/F)
- Maladies (texte générique)
- Allergies (texte générique)
- Diagnostics récents (texte générique)

❌ **JAMAIS envoyé** :
- Nom du patient
- Email
- Adresse
- Numéro de téléphone
- Numéro de sécurité sociale
- Identifiants uniques

### Conformité RGPD

- ✅ Données médicales anonymisées
- ✅ Pas de stockage des données par Groq (selon leurs CGU)
- ✅ Traitement en temps réel uniquement
- ✅ Fallback local si API désactivée

## 🧪 Tests

### Test 1 : Avec API Groq Configurée

```bash
# 1. Configurer la clé API dans .env
GROQ_API_KEY=gsk_votre_cle

# 2. Vider le cache
php bin/console cache:clear

# 3. Se connecter en tant que patient
# Email: patient.test@mediconnect.com
# Mot de passe: test123

# 4. Vérifier le badge "Propulsé par Groq Llama"
```

### Test 2 : Sans API Groq (Fallback)

```bash
# 1. Désactiver la clé API dans .env
GROQ_API_KEY=your_groq_api_key_here

# 2. Vider le cache
php bin/console cache:clear

# 3. Se connecter en tant que patient

# 4. Vérifier le badge "Mode Basique"
# Le système utilise les mots-clés classiques
```

### Test 3 : Créer des Événements de Test

```bash
# Se connecter en tant qu'organisateur et créer :

1. "Atelier Gestion du Diabète et Nutrition"
   → Le patient diabétique devrait le voir en premier

2. "Conférence Prévention Cardiovasculaire"
   → Recommandé pour l'hypertension

3. "Journée Mondiale de la Santé"
   → Score plus faible, recommandation générale
```

## 📈 Limites de l'API Groq (Gratuit)

- **Requêtes/minute** : 30
- **Requêtes/jour** : 14,400
- **Tokens/minute** : 6,000

Pour MediConnect, c'est largement suffisant :
- 1 recommandation = ~500 tokens
- Peut gérer ~12 patients/minute
- ~17,000 patients/jour

## 🔧 Dépannage

### Erreur : "Groq API key not configured"

**Solution** : Vérifiez que `GROQ_API_KEY` est défini dans `.env`

```bash
# Vérifier la configuration
php bin/console debug:container --parameter=groq_api_key
```

### Erreur : "Invalid API key"

**Solution** : Vérifiez que votre clé commence par `gsk_`

```bash
# Dans .env
GROQ_API_KEY=gsk_votre_cle_ici  # ✅ Correct
GROQ_API_KEY=sk_votre_cle       # ❌ Incorrect (OpenAI)
```

### Les recommandations sont génériques

**Solution** : Vérifiez que le patient a un dossier médical complet

```bash
php bin/console doctrine:query:sql "
  SELECT dm.* 
  FROM dossier_medical dm 
  WHERE dm.patient_id = [ID_PATIENT]
"
```

### Badge "Mode Basique" au lieu de "Groq Llama"

**Causes possibles** :
1. Clé API non configurée
2. Clé API invalide
3. Problème de connexion à Groq
4. Cache non vidé

**Solution** :
```bash
# 1. Vérifier la clé
cat .env | grep GROQ_API_KEY

# 2. Vider le cache
php bin/console cache:clear

# 3. Vérifier les logs
tail -f var/log/dev.log
```

## 🎯 Prochaines Améliorations

### Court Terme

- [ ] Ajouter le champ `genre` dans l'entité Patient
- [ ] Implémenter Octobre Rose pour les femmes
- [ ] Ajouter plus de contexte médical au prompt

### Moyen Terme

- [ ] Cache des recommandations (24h)
- [ ] Historique des recommandations acceptées
- [ ] Feedback utilisateur pour améliorer l'IA

### Long Terme

- [ ] Fine-tuning du modèle Llama sur données médicales
- [ ] Recommandations proactives par email
- [ ] Intégration avec calendrier patient

## 📞 Support

### Problème avec Groq

- Documentation : https://console.groq.com/docs
- Support : https://console.groq.com/support
- Status : https://status.groq.com/

### Problème avec MediConnect

1. Vérifier les logs : `var/log/dev.log`
2. Vider le cache : `php bin/console cache:clear`
3. Tester le fallback : Désactiver la clé API

## 🎉 Résumé

✅ **Système IA Opérationnel**
- Service GroqAIService créé
- Intégration dans EventRecommendationService
- Fallback automatique si API indisponible
- Interface utilisateur mise à jour
- Documentation complète

✅ **Prêt pour la Production**
- Données anonymisées (RGPD)
- Gestion des erreurs robuste
- Performance optimisée
- Tests validés

🚀 **Obtenez votre clé API Groq et profitez de recommandations IA intelligentes !**

https://console.groq.com/keys
