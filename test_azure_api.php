<?php
/**
 * Azure Translator API Test Script
 * 
 * This script tests your Azure Translator API credentials directly
 * Run: php test_azure_api.php
 */

// Load environment variables
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Get credentials from environment
$apiKey = $_ENV['AZURE_TRANSLATOR_API_KEY'] ?? '';
$region = $_ENV['AZURE_TRANSLATOR_REGION'] ?? '';

echo "=== Azure Translator API Test ===\n\n";

// Validate credentials
if (empty($apiKey)) {
    echo "❌ ERROR: AZURE_TRANSLATOR_API_KEY is not set in .env\n";
    exit(1);
}

if (empty($region)) {
    echo "❌ ERROR: AZURE_TRANSLATOR_REGION is not set in .env\n";
    exit(1);
}

echo "✓ API Key found: " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -5) . "\n";
echo "✓ Region: $region\n\n";

// Test translation
$testText = "Hello, how are you?";
$targetLanguage = "fr";

echo "Testing translation...\n";
echo "Text: $testText\n";
echo "Target: $targetLanguage\n\n";

$url = "https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&to=$targetLanguage&textType=plain";

$data = json_encode([
    ['text' => $testText]
]);

$headers = [
    'Ocp-Apim-Subscription-Key: ' . $apiKey,
    'Ocp-Apim-Subscription-Region: ' . $region,
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing only

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
    exit(1);
}

if ($httpCode === 200) {
    echo "✅ SUCCESS! Translation API is working!\n\n";
    
    $result = json_decode($response, true);
    if (isset($result[0]['translations'][0]['text'])) {
        $translatedText = $result[0]['translations'][0]['text'];
        echo "Original: $testText\n";
        echo "Translated: $translatedText\n";
    } else {
        echo "Response: $response\n";
    }
} elseif ($httpCode === 401) {
    echo "❌ AUTHENTICATION FAILED (401 Unauthorized)\n\n";
    echo "Possible causes:\n";
    echo "1. API key is invalid or expired\n";
    echo "2. API key doesn't match the region\n";
    echo "3. Azure subscription is not active\n";
    echo "4. Billing is not enabled for this resource\n\n";
    echo "Solutions:\n";
    echo "1. Go to Azure Portal: https://portal.azure.com\n";
    echo "2. Navigate to: Cognitive Services → Translator\n";
    echo "3. Check 'Keys and Endpoint' section\n";
    echo "4. Copy a fresh key and update your .env file\n";
    echo "5. Verify the region matches (should be: $region)\n\n";
    echo "Response: $response\n";
} elseif ($httpCode === 403) {
    echo "❌ ACCESS FORBIDDEN (403)\n";
    echo "Your API key is valid but doesn't have permission for this operation.\n";
    echo "Response: $response\n";
} else {
    echo "❌ ERROR: Unexpected HTTP status code\n";
    echo "Response: $response\n";
}

echo "\n=== Test Complete ===\n";
