# 🌤️ CONFIGURATION MÉTÉO - Weather Setup Guide

## Vue d'ensemble
Le système d'événements affiche maintenant la météo du jour de l'événement en temps réel, intégrée depuis l'API OpenWeatherMap.

---

## 📋 Prérequis

### 1. Obtenir une clé API OpenWeatherMap (GRATUIT)

#### Étapes:
1. Aller sur [https://openweathermap.org/](https://openweathermap.org/)
2. Cliquer sur **"Sign Up"**
3. Créer un compte avec votre email
4. Valider votre email
5. Aller dans **"API keys"** depuis votre dashboard
6. Copier votre clé API (exemple: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`)

> **Note**: Les clés d'API OpenWeatherMap sont gratuites avec un plan 1,000 appels/jour

---

## ⚙️ Configuration

### 2. Ajouter la clé API au fichier `.env`

Ouvrir le fichier `.env` à la racine du projet:

```bash
# .env (ou .env.local pour les environnements spécifiques)

# Ajouter cette ligne:
WEATHER_API_KEY=votre_clé_api_ici
```

**Exemple complet:**
```env
WEATHER_API_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

### 3. Configuration pour les environnements

#### Développement (`.env.local`)
```env
WEATHER_API_KEY=votre_clé_dev
```

#### Production (`.env.prod.local`)
```env
WEATHER_API_KEY=votre_clé_prod
```

#### Tests (`.env.test`)
```env
WEATHER_API_KEY=test_key_optional
```

---

## 🔧 Service WeatherService

### Localisation
```
src/Service/WeatherService.php
```

### Méthodes disponibles

#### `getWeather(string $location, \DateTimeInterface $eventDate): ?array`
Récupère les données météo brutes de l'API

```php
$weather = $this->weatherService->getWeather('Paris, France', new DateTime());
// Retourne: Array avec temperature, humidity, windSpeed, etc.
```

#### `formatWeather(array $weather): array`
Formate les données pour l'affichage en template

```php
$formatted = $this->weatherService->formatWeather($weather);
// Retourne: Array avec temperature (°C), humidity (%), windSpeed (m/s), etc.
```

#### `getWeatherEmoji(string $description): string`
Retourne un emoji basé sur la description météo

```php
$emoji = $this->weatherService->getWeatherEmoji('Clear');
// Retourne: "☀️"
```

#### `getWeatherIconUrl(string $iconCode): string`
Retourne l'URL de l'icône OpenWeatherMap

```php
$icon = $this->weatherService->getWeatherIconUrl('01d');
// Retourne: "https://openweathermap.org/img/wn/01d@2x.png"
```

---

## 📍 Intégration dans le Contrôleur

### EvenementController.php

```php
public function show(Evenement $item): Response
{
    // ... code existant ...
    
    // Fetch weather data if location and date are available
    $weather = null;
    if ($item->getLocation() && $item->getEventDate()) {
        $weather = $this->weatherService->getWeather($item->getLocation(), $item->getEventDate());
        if ($weather) {
            $weather = $this->weatherService->formatWeather($weather);
        }
    }
    
    return $this->render('evenement/show.html.twig', [
        'item' => $item,
        'participants' => $participants,
        'weather' => $weather
    ]);
}
```

---

## 🎨 Affichage Template

### Dans `templates/evenement/show.html.twig`

```twig
{% if weather %}
    <div class="card mb-4" style="border-left: 4px solid #667eea;">
        <div class="card-body">
            <h5 class="mb-3"><i class="bi bi-cloud-sun me-2"></i>Météo du jour de l'événement</h5>
            
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <div style="font-size: 4rem;">{{ weather.emoji }}</div>
                    <div style="font-size: 3rem; font-weight: bold; color: #667eea;">{{ weather.temperature }}</div>
                </div>
                
                <div class="col-md-4">
                    <strong>Condition:</strong> {{ weather.descriptionFr }}
                </div>
                
                <div class="col-md-4">
                    <div>Humidité: {{ weather.humidity }}</div>
                    <div>Vent: {{ weather.windSpeed }}</div>
                </div>
            </div>
        </div>
    </div>
{% endif %}
```

---

## 📊 Structure des données météo

### Données brutes (getWeather)
```php
[
    'location' => 'Paris',
    'country' => 'FR',
    'temperature' => 15,          // en °C
    'feelsLike' => 14,            // ressenti
    'humidity' => 65,             // en %
    'pressure' => 1013,           // en hPa
    'windSpeed' => 4.5,           // en m/s
    'description' => 'Clouds',    // en anglais
    'descriptionFr' => 'Nuageux', // en français
    'icon' => '04d',              // code icône OpenWeatherMap
    'cloudiness' => 75,           // en %
    'sunrise' => 1640000000,      // timestamp Unix
    'sunset' => 1640040000,       // timestamp Unix
]
```

### Données formatées (formatWeather)
```php
[
    'location' => 'Paris (FR)',
    'temperature' => '15°C',
    'feelsLike' => '14°C',
    'humidity' => '65%',
    'windSpeed' => '4.5 m/s',
    'description' => 'Clouds',
    'descriptionFr' => 'Nuageux',
    'emoji' => '☁️',
    'iconUrl' => 'https://openweathermap.org/img/wn/04d@2x.png',
    'cloudiness' => '75%',
    'pressure' => '1013 hPa',
]
```

---

## 🚨 Gestion des erreurs

### Pas d'API key configurée
Si `WEATHER_API_KEY` est vide ou manquant:
- La météo n'affiche pas (gracieux, pas d'erreur)
- Un message de log informatif est créé
- L'événement s'affiche normalement sans section météo

### Lieu invalide
Si le lieu n'est pas reconnu par OpenWeatherMap:
- La fonction retourne `null`
- Pas d'affichage de météo
- Pas d'erreur utilisateur

### Erreur API
En cas d'erreur de l'API (limite dépassée, problème réseau):
- Le service enregistre l'erreur dans les logs
- La météo n'affiche pas
- L'événement fonctionne normalement

---

## 🧪 Tester la météo

### 1. Configuration locale
```env
# .env.local
WEATHER_API_KEY=votre_clé_api
```

### 2. Créer un événement
- Titre: "Test Météo"
- Lieu: "Paris, France" (utilisez un lieu réel)
- Date: Aujourd'hui ou demain
- Description: Test

### 3. Voir la détail de l'événement
- Aller à `/evenement/{id}`
- Voir la section météo avec:
  - Emoji météo 🌤️☁️🌧️
  - Température
  - Description
  - Humidité, Vent, Pression

### 4. Vérifier les logs
```bash
tail -f var/log/dev.log | grep -i weather
```

---

## 🔐 Sécurité

### ✅ Points sécurisés
- API key stockée dans `.env` (jamais en production)
- Validation des entrées (localisation requise)
- Gestion d'erreur gracieuse
- Pas d'exposition de données sensibles

### ⚠️ Recommandations
1. **Ne jamais committer `.env.local`** dans Git
2. **Garder les clés API secrètes** sur serveur production
3. **Monitorer les appels API** pour détecter les abus
4. **Utiliser des quotas d'API** si applicable

---

## 📈 Limite de quotas

### Plan gratuit OpenWeatherMap
- **1,000 appels/jour** (suffisant pour ~30 consultations d'événements/jour)
- **5 appels/minute** par défaut

### Optimisation
Pour économiser les quotas:
- Ajouter un cache (15-30 min)
- Mettre en cache par localisation + date
- Utiliser Redis ou APCu

### Exemple cache (futur)
```php
// Cache 30 minutes par localisation
$cacheKey = 'weather_' . md5($location);
$weather = $cache->get($cacheKey, function() {
    return $this->getWeather($location);
});
```

---

## 📚 Ressources

- OpenWeatherMap API: https://openweathermap.org/api
- Documentation: https://openweathermap.org/weather-conditions
- Codes icônes: https://openweathermap.org/weather-conditions
- Libre de droit: Licence CC0

---

## ✅ Checklist déploiement

- [ ] Créer compte OpenWeatherMap
- [ ] Obtenir la clé API
- [ ] Ajouter `WEATHER_API_KEY` dans `.env.local`
- [ ] Tester localement avec un événement
- [ ] Vérifier affichage météo
- [ ] Vérifier logs des erreurs (le cas échéant)
- [ ] Ajouter WEATHER_API_KEY à `.env.prod` sur serveur
- [ ] Tester en production
- [ ] Monitorer les quotas API

---

**Status**: ✅ **WEATHER FEATURE COMPLETE**

La météo est maintenant affichée sur tous les événements!
