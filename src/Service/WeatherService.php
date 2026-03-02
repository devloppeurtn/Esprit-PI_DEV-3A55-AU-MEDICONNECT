<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WeatherService
{
    private const API_URL = 'https://api.openweathermap.org/data/2.5/forecast';
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        ParameterBagInterface $params
    ) {
        // Récupérer la clé API depuis les variables d'environnement
        $this->apiKey = $params->get('openweather_api_key');
    }

    /**
     * Récupère la météo pour une date et une ville données
     * 
     * @param \DateTimeInterface $date Date de l'événement
     * @param string $location Ville (ex: "Tunis,TN")
     * @return array|null Données météo ou null si erreur
     */
    public function getWeatherForDate(\DateTimeInterface $date, string $location = 'Tunis,TN'): ?array
    {
        try {
            // Si pas de clé API ou clé de démo, utiliser les données simulées
            if (empty($this->apiKey) || $this->apiKey === 'demo_key_get_your_own_at_openweathermap_org') {
                return $this->getSimulatedWeather($date);
            }

            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => [
                    'q' => $location,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr'
                ],
                'timeout' => 3, // Timeout de 3 secondes
                'max_duration' => 5 // Durée maximale de 5 secondes
            ]);

            $data = $response->toArray();
            
            // Trouver la prévision la plus proche de la date
            $targetTimestamp = $date->getTimestamp();
            $closestForecast = null;
            $minDiff = PHP_INT_MAX;

            foreach ($data['list'] as $forecast) {
                $forecastTimestamp = $forecast['dt'];
                $diff = abs($targetTimestamp - $forecastTimestamp);
                
                if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $closestForecast = $forecast;
                }
            }

            if ($closestForecast) {
                return [
                    'temp' => round($closestForecast['main']['temp']),
                    'description' => $closestForecast['weather'][0]['description'],
                    'icon' => $closestForecast['weather'][0]['icon'],
                    'humidity' => $closestForecast['main']['humidity'],
                    'wind_speed' => $closestForecast['wind']['speed']
                ];
            }

            // Si aucune prévision trouvée, utiliser les données simulées
            return $this->getSimulatedWeather($date);

        } catch (\Exception $e) {
            // En cas d'erreur, retourner des données simulées
            return $this->getSimulatedWeather($date);
        }
    }

    /**
     * Génère des données météo simulées basées sur la date
     */
    private function getSimulatedWeather(\DateTimeInterface $date): array
    {
        $month = (int) $date->format('m');
        $day = (int) $date->format('d');
        
        // Simuler différentes conditions selon la saison
        if ($month >= 6 && $month <= 8) {
            // Été
            $conditions = [
                ['temp' => 32, 'description' => 'Ensoleillé', 'icon' => '01d'],
                ['temp' => 28, 'description' => 'Partiellement nuageux', 'icon' => '02d'],
                ['temp' => 30, 'description' => 'Ciel dégagé', 'icon' => '01d'],
            ];
        } elseif ($month >= 12 || $month <= 2) {
            // Hiver
            $conditions = [
                ['temp' => 15, 'description' => 'Nuageux', 'icon' => '03d'],
                ['temp' => 12, 'description' => 'Pluie légère', 'icon' => '10d'],
                ['temp' => 18, 'description' => 'Partiellement nuageux', 'icon' => '02d'],
            ];
        } else {
            // Printemps/Automne
            $conditions = [
                ['temp' => 22, 'description' => 'Ensoleillé', 'icon' => '01d'],
                ['temp' => 20, 'description' => 'Nuageux', 'icon' => '03d'],
                ['temp' => 18, 'description' => 'Pluie', 'icon' => '10d'],
            ];
        }

        $index = $day % count($conditions);
        $weather = $conditions[$index];
        
        return [
            'temp' => $weather['temp'],
            'description' => $weather['description'],
            'icon' => $weather['icon'],
            'humidity' => rand(40, 80),
            'wind_speed' => rand(5, 20)
        ];
    }

    /**
     * Retourne l'URL de l'icône météo
     */
    public function getWeatherIconUrl(string $iconCode): string
    {
        return "https://openweathermap.org/img/wn/{$iconCode}@2x.png";
    }
}
