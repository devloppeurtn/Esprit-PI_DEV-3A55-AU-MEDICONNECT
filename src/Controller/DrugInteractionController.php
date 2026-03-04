<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/drug-checker')]
#[IsGranted('ROLE_USER')]
class DrugInteractionController extends AbstractController
{
    private string $openFdaApiUrl = 'https://api.fda.gov/drug/interaction.json';

    #[Route('/', name: 'app_drug_checker_index')]
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('drug_checker/index.html.twig');
    }

    #[Route('/api/search', name: 'app_drug_checker_search', methods: ['GET'])]
    public function searchDrugs(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json(['drugs' => []]);
        }

        try {
            $url = 'https://api.fda.gov/drug/ndc.json?search=generic_name:' . urlencode($query) . '&limit=10';
            $response = @file_get_contents($url);
            
            if ($response === false) {
                return $this->json(['drugs' => []]);
            }

            $data = json_decode($response, true);
            $drugs = [];

            if (isset($data['results'])) {
                foreach ($data['results'] as $result) {
                    $genericName = $result['generic_name'] ?? 'Unknown';
                    if (!in_array($genericName, $drugs)) {
                        $drugs[] = $genericName;
                    }
                }
            }

            return $this->json(['drugs' => array_slice($drugs, 0, 10)]);
        } catch (\Exception $e) {
            return $this->json(['drugs' => [], 'error' => 'Search failed']);
        }
    }

    #[Route('/api/check-interaction', name: 'app_drug_checker_check', methods: ['POST'])]
    public function checkInteraction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $drug1 = $data['drug1'] ?? '';
        $drug2 = $data['drug2'] ?? '';

        if (empty($drug1) || empty($drug2)) {
            return $this->json(['error' => 'Both drugs are required'], 400);
        }

        try {
            $url = $this->openFdaApiUrl . '?search=drug1:' . urlencode($drug1) . '+AND+drug2:' . urlencode($drug2);
            $response = @file_get_contents($url);
            
            if ($response === false) {
                return $this->json([
                    'interaction' => false,
                    'message' => 'No interaction data found. Please consult a pharmacist.',
                    'severity' => 'unknown'
                ]);
            }

            $data = json_decode($response, true);

            if (isset($data['results']) && count($data['results']) > 0) {
                $interaction = $data['results'][0];
                return $this->json([
                    'interaction' => true,
                    'drug1' => $interaction['drug1'][0]['generic_name'] ?? $drug1,
                    'drug2' => $interaction['drug2'][0]['generic_name'] ?? $drug2,
                    'description' => $interaction['interaction'] ?? 'Interaction found',
                    'severity' => $this->determineSeverity($interaction),
                    'message' => 'Potential interaction detected!'
                ]);
            }

            return $this->json([
                'interaction' => false,
                'drug1' => $drug1,
                'drug2' => $drug2,
                'message' => 'No known interactions found between these drugs.',
                'severity' => 'none'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'interaction' => false,
                'message' => 'Error checking interactions. Please try again.',
                'error' => $e->getMessage()
            ]);
        }
    }

    private function determineSeverity(array $interaction): string
    {
        $description = strtolower($interaction['interaction'] ?? '');
        
        if (strpos($description, 'contraindicated') !== false || strpos($description, 'severe') !== false) {
            return 'severe';
        } elseif (strpos($description, 'significant') !== false || strpos($description, 'moderate') !== false) {
            return 'moderate';
        } elseif (strpos($description, 'minor') !== false) {
            return 'minor';
        }
        
        return 'unknown';
    }
}
