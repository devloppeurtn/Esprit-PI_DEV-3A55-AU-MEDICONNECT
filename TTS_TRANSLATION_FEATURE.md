# 🔊 TTS with Translation Support

## What's New

The Text-to-Speech (TTS) feature now intelligently reads translated content when available!

## How It Works

### Before Translation
- Click "Écouter" button → Reads the original French content
- Uses French voice (fr-FR)

### After Translation
1. Select a language from the dropdown (English, Spanish, German, etc.)
2. Click "Traduire" button → Content is translated
3. Click "Écouter" button → **Now reads the TRANSLATED content!**
4. Uses the appropriate voice for the target language

### Closing Translation
- Click the X button on the translation card
- TTS automatically switches back to reading the original French content

## Features

✅ **Smart Language Detection**: Automatically uses the correct voice for each language
✅ **Seamless Switching**: No need to reload the page
✅ **Visual Feedback**: Button shows loading state and play/stop status
✅ **Multi-language Support**: Works with all 16 supported languages

## Supported Languages for TTS

| Language | Code | Voice Locale |
|----------|------|--------------|
| Français | fr | fr-FR |
| English | en | en-US |
| Español | es | es-ES |
| Deutsch | de | de-DE |
| Italiano | it | it-IT |
| Português | pt | pt-PT |
| العربية | ar | ar-SA |
| 中文 (简体) | zh-Hans | zh-CN |
| 中文 (繁體) | zh-Hant | zh-TW |
| 日本語 | ja | ja-JP |
| Русский | ru | ru-RU |
| हिन्दी | hi | hi-IN |
| Nederlands | nl | nl-NL |
| Polski | pl | pl-PL |
| Türkçe | tr | tr-TR |
| 한국어 | ko | ko-KR |

## User Experience Flow

```
1. User views course content in French
   ↓
2. User clicks "Écouter" → Hears French audio ✅
   ↓
3. User selects "English" and clicks "Traduire"
   ↓
4. Translation appears in a card below
   ↓
5. User clicks "Écouter" again → Hears ENGLISH audio ✅
   ↓
6. User closes translation card
   ↓
7. User clicks "Écouter" → Back to French audio ✅
```

## Technical Implementation

### Frontend (cours.html.twig)
- Tracks current translation state with `currentTranslatedText` and `currentTargetLanguage`
- TTS button checks if translation is visible before reading
- Close button resets translation state

### TTS Player (tts-player.js)
- Added `getFullLanguageCode()` method to map short codes (fr, en) to full locales (fr-FR, en-US)
- Improved voice selection to handle multiple language formats
- Maintains backward compatibility with existing code

### Translation Service
- Returns translated text in the response
- Handles 16 different languages
- Graceful error handling if translation fails

## Testing

### Test Scenario 1: Basic Translation + TTS
1. Go to any course page
2. Click "Écouter" → Should hear French
3. Select "English" and click "Traduire"
4. Wait for translation to appear
5. Click "Écouter" → Should hear English

### Test Scenario 2: Multiple Languages
1. Translate to Spanish → Click "Écouter" → Hear Spanish
2. Close translation
3. Translate to German → Click "Écouter" → Hear German
4. Close translation
5. Click "Écouter" → Back to French

### Test Scenario 3: Error Handling
1. If translation fails, TTS still works with original content
2. If browser doesn't support a language, falls back to default voice

## Browser Compatibility

The TTS feature uses the Web Speech API, which is supported by:
- ✅ Chrome/Edge (Best support)
- ✅ Safari (Good support)
- ✅ Firefox (Limited voices)
- ❌ Internet Explorer (Not supported)

## Accessibility Benefits

This feature greatly improves accessibility for:
- 👁️ Visually impaired users
- 📚 Users with reading difficulties (dyslexia)
- 🌍 Non-native French speakers learning medical content
- 🎧 Users who prefer audio learning
- 🚗 Users who want to listen while multitasking

## Future Enhancements

Potential improvements:
- [ ] Add playback speed control
- [ ] Add voice gender selection
- [ ] Add pause/resume functionality
- [ ] Save user's preferred language
- [ ] Highlight text as it's being read
- [ ] Download audio as MP3 file

## Files Modified

1. `templates/savoir_medical/cours.html.twig` - Added translation state tracking
2. `public/assets/js/tts-player.js` - Added language code mapping
3. `src/Service/TranslationService.php` - Already supports 16 languages
4. `src/Controller/TranslationController.php` - Improved error handling

## Summary

The TTS feature now intelligently adapts to show translated content, making medical education accessible to a global audience. Users can seamlessly switch between languages and hear content in their preferred language with just two clicks!
