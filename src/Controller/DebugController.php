<?php

namespace App\Controller;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    #[Route('/debug/translation-config', name: 'debug_translation_config')]
    public function debugTranslationConfig(TranslationService $translationService): JsonResponse
    {
        // Use reflection to access private properties
        $reflection = new \ReflectionClass($translationService);
        
        $apiKeyProperty = $reflection->getProperty('azureApiKey');
        $apiKeyProperty->setAccessible(true);
        $apiKey = $apiKeyProperty->getValue($translationService);
        
        $regionProperty = $reflection->getProperty('azureRegion');
        $regionProperty->setAccessible(true);
        $region = $regionProperty->getValue($translationService);
        
        return $this->json([
            'api_key_length' => strlen($apiKey),
            'api_key_preview' => substr($apiKey, 0, 10) . '...' . substr($apiKey, -5),
            'region' => $region,
            'env_api_key_preview' => substr($_ENV['AZURE_TRANSLATOR_API_KEY'] ?? 'NOT_SET', 0, 10) . '...',
            'env_region' => $_ENV['AZURE_TRANSLATOR_REGION'] ?? 'NOT_SET',
        ]);
    }
}
