# Medical Content Translation Module - Implementation Guide

## Overview

The Medical Content Translation Module enables doctors, patients, and administrators to dynamically translate platform content according to their language preferences. Using the LibreTranslate API, the system allows users to activate translation on demand when consulting educational courses, medical categories, clinical information, and AI-generated quiz questions.

## Features

✅ **Real-time Translation** - Instant translation using LibreTranslate API
✅ **15+ Languages** - English, French, Spanish, German, Italian, Portuguese, Arabic, Chinese, Japanese, Russian, Hindi, Dutch, Polish, Turkish, Korean
✅ **Auto-detection** - Automatically detect source language
✅ **Course Integration** - Translation widget embedded in course pages
✅ **Standalone Translator** - Dedicated page for translating any medical content
✅ **Batch Translation** - Translate multiple texts at once
✅ **Secure & Private** - No data storage, real-time processing
✅ **Easy to Use** - Simple UI with language selection and one-click translation

## Architecture

### Components

1. **TranslationService** (`src/Service/TranslationService.php`)
   - Core translation logic
   - Language detection
   - Batch translation support
   - Error handling and logging

2. **TranslationController** (`src/Controller/TranslationController.php`)
   - API endpoints for translation
   - Language management
   - Batch operations

3. **TranslationPageController** (`src/Controller/TranslationPageController.php`)
   - Main translation page route

4. **Templates**
   - `templates/translation/index.html.twig` - Standalone translator page
   - `templates/translation/_translation_widget.html.twig` - Reusable widget
   - `templates/savoir_medical/cours.html.twig` - Updated with translation widget

## Supported Languages

| Code | Language | Code | Language |
|------|----------|------|----------|
| en | English | ar | العربية (Arabic) |
| fr | Français | zh | 中文 (Chinese) |
| es | Español | ja | 日本語 (Japanese) |
| de | Deutsch | ru | Русский (Russian) |
| it | Italiano | hi | हिन्दी (Hindi) |
| pt | Português | nl | Nederlands |
| | | pl | Polski |
| | | tr | Türkçe |
| | | ko | 한국어 (Korean) |

## Usage

### For Students & Patients

#### Standalone Translation Page
1. Navigate to "Traduction" in the sidebar under "Outils"
2. Paste medical content or course text
3. Select target language
4. Click "Translate"
5. View translated content instantly

#### In Course Pages
1. Open any course
2. Click "Traduire" button next to course content
3. Select target language
4. Click checkmark to apply translation
5. Use reset button to return to original

### For Doctors & Administrators

#### Translating Course Content
1. Create or edit a course
2. Use the translation widget to preview content in different languages
3. Ensure medical terminology is preserved
4. Publish course with translation support

#### Batch Translation
Use the API to translate multiple course materials at once:
```bash
POST /api/translation/translate-batch
{
  "texts": ["Text 1", "Text 2", "Text 3"],
  "targetLanguage": "es",
  "sourceLanguage": "auto"
}
```

## API Endpoints

### Translate Single Text
```
POST /api/translation/translate
Content-Type: application/json

{
  "text": "The heart is a muscular organ...",
  "targetLanguage": "fr",
  "sourceLanguage": "auto"
}

Response:
{
  "original": "The heart is a muscular organ...",
  "translated": "Le cœur est un organe musculaire...",
  "targetLanguage": "fr",
  "sourceLanguage": "auto"
}
```

### Translate Multiple Texts
```
POST /api/translation/translate-batch
Content-Type: application/json

{
  "texts": ["Text 1", "Text 2"],
  "targetLanguage": "es",
  "sourceLanguage": "auto"
}

Response:
{
  "results": {
    "0": "Translated text 1",
    "1": "Translated text 2"
  },
  "targetLanguage": "es",
  "sourceLanguage": "auto"
}
```

### Get Supported Languages
```
GET /api/translation/languages

Response:
{
  "languages": {
    "en": "English",
    "fr": "Français",
    "es": "Español",
    ...
  }
}
```

### Detect Language
```
POST /api/translation/detect
Content-Type: application/json

{
  "text": "Bonjour, comment allez-vous?"
}

Response:
{
  "detectedLanguage": "fr",
  "languageName": "Français"
}
```

## Routes

| Route | Method | Purpose |
|-------|--------|---------|
| `/translation/` | GET | Main translation page |
| `/api/translation/translate` | POST | Translate single text |
| `/api/translation/translate-batch` | POST | Translate multiple texts |
| `/api/translation/languages` | GET | Get supported languages |
| `/api/translation/detect` | POST | Detect language |

## Integration Examples

### In Twig Templates

#### Using Translation Widget
```twig
{% set widget_id = 'my-content-' ~ item.id %}
{% include 'translation/_translation_widget.html.twig' %}

<div id="translation-content-{{ widget_id }}">
    {{ content }}
</div>
```

#### Manual Translation in JavaScript
```javascript
fetch('/api/translation/translate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        text: 'Medical content here',
        targetLanguage: 'es',
        sourceLanguage: 'auto'
    })
})
.then(r => r.json())
.then(data => {
    console.log(data.translated);
});
```

## Technical Details

### Data Source
- **LibreTranslate API** - Free, open-source translation service
- **Endpoint** - https://libretranslate.com/translate
- **No API Key Required** - Public API access
- **Rate Limiting** - Reasonable limits for educational use

### Performance
- **Caching** - Translations are not cached (real-time)
- **Timeout** - 10 seconds per request
- **Batch Processing** - Sequential translation for multiple texts
- **Error Handling** - Graceful fallback on API failures

### Security
- **No Data Storage** - Translations processed in real-time
- **HTTPS Only** - Secure communication with API
- **Authentication** - Requires ROLE_USER
- **Input Validation** - Text length and language validation

### Browser Compatibility
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers

## Customization

### Adding New Languages

Edit `src/Service/TranslationService.php`:

```php
private const SUPPORTED_LANGUAGES = [
    'en' => 'English',
    'your_code' => 'Your Language', // Add here
];
```

### Styling

Modify CSS in:
- `templates/translation/index.html.twig` - Main page styles
- `templates/translation/_translation_widget.html.twig` - Widget styles

### Translation Quality

For better medical terminology preservation:
1. Use specific medical terms in source text
2. Verify translations for accuracy
3. Consider professional translation for critical content
4. Test with native speakers

## Troubleshooting

### Translation Not Working
1. Check internet connection
2. Verify LibreTranslate API is accessible
3. Check browser console for errors
4. Verify language code is supported

### Slow Translation
1. Check network speed
2. Reduce text length
3. Try batch translation
4. Check API status

### Incorrect Translations
1. Verify source language is correct
2. Check for special characters
3. Use simpler language structure
4. Consider professional translation for critical content

## Performance Optimization

### Best Practices
1. Translate only visible content
2. Use batch translation for multiple texts
3. Cache translations on client-side if needed
4. Limit translation frequency
5. Use auto-detection for source language

### Monitoring
- Log all translation requests
- Monitor API response times
- Track error rates
- Analyze usage patterns

## Future Enhancements

- [ ] Translation caching for frequently used content
- [ ] Custom medical terminology dictionary
- [ ] Translation quality scoring
- [ ] User feedback on translations
- [ ] Integration with professional translation services
- [ ] Offline translation support
- [ ] Translation history and management
- [ ] Multi-language course creation
- [ ] Automatic content translation on upload
- [ ] Translation analytics and reporting

## Support

For issues or feature requests, contact the development team or check the MediConnect documentation.

---

**Last Updated:** February 26, 2026
**Version:** 1.0.0
**API:** LibreTranslate (Free)
