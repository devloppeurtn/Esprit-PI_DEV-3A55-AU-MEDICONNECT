<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class TextToSpeechService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Synthesize text to speech using browser's Web Speech API
     * This method returns a flag to use client-side TTS
     * 
     * @param string $text The text to convert to speech
     * @param string $language Language code (e.g., 'fr-FR', 'en-US')
     * @param string $voice Voice type (e.g., 'male', 'female')
     * @return array Response indicating to use browser TTS
     */
    public function synthesize(string $text, string $language = 'fr-FR', string $voice = 'female'): array
    {
        // Use browser's Web Speech API - more reliable and no external dependencies
        return [
            'success' => true,
            'use_browser_tts' => true,
            'text' => $text,
            'language' => $language,
            'voice' => $voice,
        ];
    }

    /**
     * Get available voices for a language
     */
    public function getAvailableVoices(string $language = 'fr'): array
    {
        return [
            'fr' => [
                ['id' => 'fr-FR-female', 'name' => 'Voix féminine française', 'lang' => 'fr-FR'],
                ['id' => 'fr-FR-male', 'name' => 'Voix masculine française', 'lang' => 'fr-FR'],
            ],
            'en' => [
                ['id' => 'en-US-female', 'name' => 'Female Voice (US)', 'lang' => 'en-US'],
                ['id' => 'en-US-male', 'name' => 'Male Voice (US)', 'lang' => 'en-US'],
                ['id' => 'en-GB-female', 'name' => 'Female Voice (UK)', 'lang' => 'en-GB'],
            ],
        ][$language] ?? [];
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): array
    {
        return [
            'fr' => 'Français',
            'en' => 'English',
        ];
    }
}
