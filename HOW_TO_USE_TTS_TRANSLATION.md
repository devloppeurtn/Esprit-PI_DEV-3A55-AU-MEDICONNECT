# 📖 How to Use TTS with Translation

## Quick Start Guide

### Step 1: View Course Content
Navigate to any course page. You'll see:
- Course content in French
- "Écouter" button (Listen button)
- Language dropdown and "Traduire" button (Translate button)

### Step 2: Listen to Original Content
```
Click "Écouter" → Hears French audio
```
The button will change to show a stop icon while playing.

### Step 3: Translate Content
```
1. Select language from dropdown (e.g., "English")
2. Click "Traduire" button
3. Wait for translation to appear (2-3 seconds)
```
You'll see a success message: "Traduction réussie! Cliquez sur 'Écouter' pour entendre la traduction."

### Step 4: Listen to Translated Content
```
Click "Écouter" again → Now hears ENGLISH audio!
```
The TTS automatically detects the translation and reads it in the target language.

### Step 5: Switch Back to Original
```
Click the X button on the translation card
Click "Écouter" → Back to French audio
```

## Example Workflow

### Scenario: French doctor wants to share content with English-speaking patient

1. **Doctor opens course**: "Les Maladies Cardiovasculaires"
2. **Original content** (French):
   ```
   "Les maladies cardiovasculaires sont la première cause de mortalité 
   dans le monde. Elles regroupent l'ensemble des pathologies affectant 
   le cœur et les vaisseaux sanguins..."
   ```

3. **Doctor clicks "Écouter"**: 
   - 🔊 Hears: "Les maladies cardiovasculaires sont..."
   - Voice: French (fr-FR)

4. **Doctor selects "English" and clicks "Traduire"**:
   - ⏳ Loading... (2 seconds)
   - ✅ Translation appears:
   ```
   "Cardiovascular diseases are the leading cause of death worldwide. 
   They include all pathologies affecting the heart and blood vessels..."
   ```

5. **Doctor clicks "Écouter" again**:
   - 🔊 Hears: "Cardiovascular diseases are..."
   - Voice: English (en-US)

6. **Patient can now listen in their language!**

## Tips & Tricks

### Tip 1: Quick Language Switching
You can translate to multiple languages without reloading:
```
French → English → Spanish → German → Back to French
```
Each time you click "Écouter", it reads in the current language.

### Tip 2: Stop Playback
Click the "Écouter" button again while audio is playing to stop it immediately.

### Tip 3: Translation Stays Visible
The translation card stays open until you close it, so you can:
- Read the translation
- Listen to it multiple times
- Compare with the original

### Tip 4: Best Languages for TTS
Some languages have better voice quality:
- ⭐⭐⭐ Excellent: English, French, Spanish, German
- ⭐⭐ Good: Italian, Portuguese, Japanese, Korean
- ⭐ Basic: Arabic, Hindi, Chinese

### Tip 5: Adjust Browser Volume
Use your browser's volume control for better audio experience.

## Troubleshooting

### Problem: No audio plays
**Solution**: 
- Check browser volume
- Try a different browser (Chrome works best)
- Reload the page

### Problem: Translation fails
**Solution**:
- Check your internet connection
- Verify Azure API is configured (see GITHUB_PUSH_READY.md)
- Try again in a few seconds

### Problem: Wrong language voice
**Solution**:
- Your browser might not have that language installed
- It will use the closest available voice
- Try Chrome for best language support

### Problem: Audio cuts off
**Solution**:
- This is a browser limitation for very long texts
- The text is automatically split into smaller chunks
- Just click "Écouter" again to continue

## Keyboard Shortcuts (Future Feature)

Coming soon:
- `Space`: Play/Pause
- `Esc`: Stop
- `Ctrl+T`: Translate
- `Ctrl+L`: Change language

## Accessibility Features

### For Screen Reader Users
- All buttons have proper ARIA labels
- Translation status is announced
- Playback state is communicated

### For Keyboard Users
- Tab through controls
- Enter to activate buttons
- Escape to close translation

### For Low Vision Users
- High contrast buttons
- Large click targets
- Clear visual feedback

## Best Practices

### For Doctors
1. Test translation before sharing with patients
2. Use simple medical terms for better translation
3. Verify critical information in both languages

### For Patients
1. Listen at comfortable speed (0.9x is default)
2. Pause and replay as needed
3. Read along while listening for better comprehension

### For Administrators
1. Monitor translation usage
2. Check for frequently translated content
3. Consider pre-translating popular courses

## Integration with Other Features

### Works With:
- ✅ Quiz questions (separate TTS)
- ✅ All course content
- ✅ Multiple choice answers
- ✅ Category descriptions

### Coming Soon:
- 📝 Appointment notes
- 💬 Chat messages
- 📧 Email notifications
- 📱 Mobile app

## Summary

The TTS + Translation feature makes medical education accessible to everyone, regardless of language. With just two clicks, users can:
1. Translate content to their language
2. Hear it spoken naturally

This is especially valuable for:
- International patients
- Medical students learning in different languages
- Doctors sharing information across language barriers
- Accessibility for visually impaired users

Enjoy learning in your preferred language! 🌍🔊
