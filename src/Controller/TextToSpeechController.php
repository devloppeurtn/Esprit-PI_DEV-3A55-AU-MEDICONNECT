<?php

namespace App\Controller;

use App\Service\TextToSpeechService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tts')]
class TextToSpeechController extends AbstractController
{
    private TextToSpeechService $ttsService;

    public function __construct(TextToSpeechService $ttsService)
    {
        $this->ttsService = $ttsService;
    }

    #[Route('/synthesize', name: 'app_tts_synthesize', methods: ['POST'])]
    public function synthesize(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['text']) || empty($data['text'])) {
            return $this->json([
                'success' => false,
                'error' => 'Text is required',
            ], 400);
        }

        $text = $data['text'];
        $language = $data['language'] ?? 'fr';
        $voice = $data['voice'] ?? 'female';

        $result = $this->ttsService->synthesize($text, $language, $voice);

        return $this->json($result);
    }

    #[Route('/voices/{language}', name: 'app_tts_voices', methods: ['GET'])]
    public function getVoices(string $language = 'fr'): JsonResponse
    {
        $voices = $this->ttsService->getAvailableVoices($language);

        return $this->json([
            'success' => true,
            'voices' => $voices,
        ]);
    }
}
