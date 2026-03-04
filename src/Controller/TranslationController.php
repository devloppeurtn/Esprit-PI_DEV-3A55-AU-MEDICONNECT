<?php

namespace App\Controller;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/translation')]
class TranslationController extends AbstractController
{
    public function __construct(private TranslationService $translationService)
    {
    }

    #[Route('/translate', name: 'app_translation_translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        $targetLanguage = $data['targetLanguage'] ?? '';
        $sourceLanguage = $data['sourceLanguage'] ?? 'auto';

        if (empty($text) || empty($targetLanguage)) {
            return $this->json(['error' => 'Text and target language are required'], 400);
        }

        if (!$this->translationService->isLanguageSupported($targetLanguage)) {
            return $this->json(['error' => 'Unsupported target language'], 400);
        }

        $translatedText = $this->translationService->translate($text, $targetLanguage, $sourceLanguage);

        if ($translatedText === null) {
            return $this->json([
                'success' => false,
                'error' => 'Translation service temporarily unavailable',
                'message' => 'Please check your Azure API key configuration or try again later',
                'original' => $text,
            ], 503);
        }

        return $this->json([
            'success' => true,
            'original' => $text,
            'translated' => $translatedText,
            'targetLanguage' => $targetLanguage,
            'sourceLanguage' => $sourceLanguage,
        ]);
    }

    #[Route('/translate-batch', name: 'app_translation_translate_batch', methods: ['POST'])]
    public function translateBatch(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $texts = $data['texts'] ?? [];
        $targetLanguage = $data['targetLanguage'] ?? '';
        $sourceLanguage = $data['sourceLanguage'] ?? 'auto';

        if (empty($texts) || empty($targetLanguage)) {
            return $this->json(['error' => 'Texts and target language are required'], 400);
        }

        if (!$this->translationService->isLanguageSupported($targetLanguage)) {
            return $this->json(['error' => 'Unsupported target language'], 400);
        }

        $results = $this->translationService->translateBatch($texts, $targetLanguage, $sourceLanguage);

        return $this->json([
            'results' => $results,
            'targetLanguage' => $targetLanguage,
            'sourceLanguage' => $sourceLanguage,
        ]);
    }

    #[Route('/languages', name: 'app_translation_languages', methods: ['GET'])]
    public function getLanguages(): JsonResponse
    {
        return $this->json([
            'languages' => $this->translationService->getSupportedLanguages(),
        ]);
    }

    #[Route('/detect', name: 'app_translation_detect', methods: ['POST'])]
    public function detectLanguage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';

        if (empty($text)) {
            return $this->json(['error' => 'Text is required'], 400);
        }

        $language = $this->translationService->detectLanguage($text);

        if ($language === null) {
            return $this->json(['error' => 'Language detection failed'], 500);
        }

        return $this->json([
            'detectedLanguage' => $language,
            'languageName' => $this->translationService->getLanguageName($language),
        ]);
    }
}
