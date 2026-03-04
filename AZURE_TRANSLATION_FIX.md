# Azure Translation API 401 Error - Solutions

## Problem
The Azure Translator API is returning a **401 Unauthorized** error, which means the API key or configuration is invalid.

## What I've Fixed
1. ✅ Added `.env` to `.gitignore` to prevent pushing secrets to GitHub
2. ✅ Created `.env.example` as a template (safe to commit)
3. ✅ Environment variables are properly configured in `services.yaml`
4. ✅ `TranslationService.php` uses constructor injection

## Solutions to Fix the 401 Error

### Option 1: Verify Your Azure API Key (RECOMMENDED)

Your Azure API key might be expired or invalid. Here's how to verify:

1. **Go to Azure Portal**: https://portal.azure.com
2. **Navigate to**: Cognitive Services → Translator resource
3. **Check Keys and Endpoint**:
   - Copy **Key 1** or **Key 2**
   - Verify the **Region** (should be `francecentral`)
4. **Update your `.env` file**:
   ```env
   AZURE_TRANSLATOR_API_KEY=your_new_key_here
   AZURE_TRANSLATOR_REGION=francecentral
   ```
5. **Clear Symfony cache**:
   ```bash
   php bin/console cache:clear
   ```

### Option 2: Make Translation Optional (QUICK FIX)

If you can't fix the Azure key right now, make translation optional so the app still works:

**Update `TranslationController.php`** to return a friendly error instead of failing:

```php
// In translate() method, change the response when translation fails:
if (!$translatedText) {
    return $this->json([
        'success' => false,
        'message' => 'Translation service temporarily unavailable. Please try again later.',
        'original_text' => $text
    ]);
}
```

### Option 3: Use Alternative Translation API

If Azure continues to fail, you can switch to a free alternative:

#### LibreTranslate (Free, Self-hosted or Public)
```php
// In TranslationService.php, add a fallback method:
private function translateViaLibre(string $text, string $targetLanguage): ?string
{
    try {
        $response = $this->httpClient->request('POST', 'https://libretranslate.com/translate', [
            'json' => [
                'q' => $text,
                'source' => 'auto',
                'target' => $targetLanguage,
                'format' => 'text'
            ],
            'timeout' => 20,
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getContent(), true);
            return $data['translatedText'] ?? null;
        }
    } catch (\Exception $e) {
        $this->logger->warning('LibreTranslate failed', ['message' => $e->getMessage()]);
    }
    
    return null;
}
```

### Option 4: Disable Translation Feature Temporarily

Comment out the translation button in your templates until you fix the API:

```twig
{# Temporarily disabled - Azure API issue
<button class="translate-btn">Traduire</button>
#}
```

## How to Push to GitHub Safely

Now that `.env` is in `.gitignore`, you can push safely:

```bash
# 1. Check what will be committed (should NOT include .env)
git status

# 2. If .env was previously committed, remove it from git history:
git rm --cached .env

# 3. Add your changes
git add .

# 4. Commit
git commit -m "Secure API keys and fix translation service"

# 5. Push to GitHub
git push origin main
```

## Testing the Fix

After updating your Azure key:

```bash
# 1. Clear cache
php bin/console cache:clear

# 2. Test translation endpoint
curl -X POST http://127.0.0.1:8000/api/translation/translate \
  -H "Content-Type: application/json" \
  -d '{"text":"Hello world","targetLanguage":"fr"}'

# Expected response:
# {"success":true,"translatedText":"Bonjour le monde"}
```

## Current Status

- ✅ Security: API keys are now in environment variables
- ✅ GitHub: `.env` is ignored, safe to push
- ❌ Translation: Still returning 401 (need to verify Azure key)

## Next Steps

1. **Verify your Azure API key** in Azure Portal (Option 1)
2. **Test the translation** after updating the key
3. **If still failing**, consider Option 2 or 3 above
4. **Push to GitHub** once translation is working or disabled

## Need Help?

- Azure Translator Docs: https://learn.microsoft.com/en-us/azure/cognitive-services/translator/
- Check if your Azure subscription is active
- Verify billing is enabled for the Translator resource
- Try regenerating the API key in Azure Portal
