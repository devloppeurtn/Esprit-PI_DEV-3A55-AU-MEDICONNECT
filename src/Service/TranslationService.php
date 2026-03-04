<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class TranslationService
{
    private const AZURE_TRANSLATOR_ENDPOINT = 'https://api.cognitive.microsofttranslator.com/translate';
    private const API_VERSION = '3.0';
    
    private const SUPPORTED_LANGUAGES = [
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ar' => 'العربية',
        'zh-Hans' => '中文 (简体)',
        'zh-Hant' => '中文 (繁體)',
        'ja' => '日本語',
        'ru' => 'Русский',
        'hi' => 'हिन्दी',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'tr' => 'Türkçe',
        'ko' => '한국어',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $azureApiKey,
        private string $azureRegion
    ) {
        // Debug: Log the API key and region on service initialization
        $this->logger->info('TranslationService initialized', [
            'api_key_length' => strlen($azureApiKey),
            'api_key_preview' => substr($azureApiKey, 0, 10) . '...' . substr($azureApiKey, -5),
            'region' => $azureRegion,
        ]);
    }

    /**
     * Translate text using Microsoft Azure Translator API
     */
    public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'auto'): ?string
    {
        if (empty($text)) {
            return null;
        }

        if (!$this->isLanguageSupported($targetLanguage)) {
            $this->logger->warning("Unsupported target language: {$targetLanguage}");
            return null;
        }

        try {
            $translatedText = $this->translateViaAzure($text, $targetLanguage, $sourceLanguage);
            
            if ($translatedText) {
                return $translatedText;
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->error('Translation service error', [
                'message' => $e->getMessage(),
                'text_length' => strlen($text),
                'target_language' => $targetLanguage,
            ]);
            return null;
        }
    }

    /**
     * Translate via Azure Translator API
     */
    private function translateViaAzure(string $text, string $targetLanguage, string $sourceLanguage = 'auto'): ?string
    {
        try {
            $textType = $this->containsHtml($text) ? 'html' : 'plain';

            $params = [
                'api-version' => self::API_VERSION,
                'to' => $targetLanguage,
                'textType' => $textType,
            ];

            if ($sourceLanguage !== 'auto') {
                $params['from'] = $sourceLanguage;
            }

            $url = self::AZURE_TRANSLATOR_ENDPOINT . '?' . http_build_query($params);

            $this->logger->info('Translating with Azure Translator API', [
                'target_language' => $targetLanguage,
                'text_length' => strlen($text),
            ]);

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => $this->azureApiKey,
                    'Ocp-Apim-Subscription-Region' => $this->azureRegion,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    ['text' => $text]
                ],
                'timeout' => 20,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $content = $response->getContent();
                $translatedText = $this->parseAzureResponse($content);

                if ($translatedText) {
                    $this->logger->info('Translation successful with Azure Translator API');
                    return $translatedText;
                }
            } else {
                $this->logger->warning('Azure Translator API HTTP error', [
                    'status_code' => $statusCode,
                ]);
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->warning('Azure translation failed', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parse Azure Translator API JSON response
     */
    private function parseAzureResponse(string $jsonContent): ?string
    {
        try {
            $data = json_decode($jsonContent, true);
            
            if (!is_array($data) || empty($data)) {
                $this->logger->error('Invalid Azure response format');
                return null;
            }

            if (isset($data[0]['translations'][0]['text'])) {
                return $data[0]['translations'][0]['text'];
            }

            $this->logger->error('Unexpected Azure response structure', ['response' => $data]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Error parsing Azure response', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if text contains HTML
     */
    private function containsHtml(string $text): bool
    {
        return preg_match('/<[^>]+>/', $text) === 1;
    }

    /**
     * Translate multiple texts at once
     */
    public function translateBatch(array $texts, string $targetLanguage, string $sourceLanguage = 'auto'): array
    {
        $results = [];

        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $targetLanguage, $sourceLanguage);
        }

        return $results;
    }

    /**
     * Check if language is supported
     */
    public function isLanguageSupported(string $language): bool
    {
        return isset(self::SUPPORTED_LANGUAGES[$language]);
    }

    /**
     * Get all supported languages
     */
    public function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Get language name by code
     */
    public function getLanguageName(string $code): ?string
    {
        return self::SUPPORTED_LANGUAGES[$code] ?? null;
    }

    /**
     * Detect language from text using Azure Translator
     */
    public function detectLanguage(string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        try {
            $url = 'https://api.cognitive.microsofttranslator.com/detect?api-version=' . self::API_VERSION;

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => $this->azureApiKey,
                    'Ocp-Apim-Subscription-Region' => $this->azureRegion,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    ['text' => substr($text, 0, 100)]
                ],
                'timeout' => 20,
            ]);

            if ($response->getStatusCode() === 200) {
                $content = $response->getContent();
                $data = json_decode($content, true);

                if (is_array($data) && isset($data[0]['language'])) {
                    return $data[0]['language'];
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->warning('Language detection failed', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
