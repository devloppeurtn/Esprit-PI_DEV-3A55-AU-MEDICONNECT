/**
 * Text-to-Speech Player Component
 * Uses browser's Web Speech API for reliable, high-quality speech synthesis
 */

class TTSPlayer {
    constructor() {
        this.synth = window.speechSynthesis;
        this.currentUtterance = null;
        this.isPlaying = false;
        this.voices = [];
        this.selectedVoice = null;
        
        // Load voices
        this.loadVoices();
        
        // Voices might load asynchronously
        if (this.synth.onvoiceschanged !== undefined) {
            this.synth.onvoiceschanged = () => this.loadVoices();
        }
    }

    /**
     * Load available voices from browser
     */
    loadVoices() {
        this.voices = this.synth.getVoices();
        
        // Set default French voice
        this.selectedVoice = this.voices.find(voice => 
            voice.lang.startsWith('fr') && voice.name.toLowerCase().includes('female')
        ) || this.voices.find(voice => voice.lang.startsWith('fr')) || this.voices[0];
    }

    /**
     * Play text as speech
     * @param {string} text - Text to convert to speech
     * @param {string} language - Language code (fr-FR, en-US)
     * @param {string} voiceType - Voice type (male, female)
     * @param {HTMLElement} button - Button element to update UI
     */
    async play(text, language = 'fr-FR', voiceType = 'female', button = null) {
        if (!text || text.trim() === '') {
            console.error('No text provided for TTS');
            return;
        }

        // Check if browser supports speech synthesis
        if (!this.synth) {
            this.showNotification('Votre navigateur ne supporte pas la synthèse vocale', 'error');
            return;
        }

        // Stop current speech if playing
        if (this.isPlaying) {
            this.stop();
            if (button) {
                this.updateButtonState(button, false);
            }
            return;
        }

        // Show loading state
        if (button) {
            this.updateButtonState(button, 'loading');
        }

        try {
            // Create utterance
            this.currentUtterance = new SpeechSynthesisUtterance(text);
            
            // Select appropriate voice
            const voice = this.selectVoice(language, voiceType);
            if (voice) {
                this.currentUtterance.voice = voice;
            }
            
            // Set language
            this.currentUtterance.lang = language;
            
            // Set speech parameters for medical content
            this.currentUtterance.rate = 0.9; // Slightly slower for clarity
            this.currentUtterance.pitch = 1.0;
            this.currentUtterance.volume = 1.0;

            // Event handlers
            this.currentUtterance.onstart = () => {
                this.isPlaying = true;
                if (button) {
                    this.updateButtonState(button, true);
                }
            };

            this.currentUtterance.onend = () => {
                this.isPlaying = false;
                if (button) {
                    this.updateButtonState(button, false);
                }
            };

            this.currentUtterance.onerror = (event) => {
                console.error('Speech synthesis error:', event);
                this.isPlaying = false;
                if (button) {
                    this.updateButtonState(button, false);
                }
                
                if (event.error !== 'interrupted' && event.error !== 'canceled') {
                    this.showNotification('Erreur lors de la lecture audio', 'error');
                }
            };

            // Speak
            this.synth.speak(this.currentUtterance);

        } catch (error) {
            console.error('TTS failed:', error);
            this.isPlaying = false;
            if (button) {
                this.updateButtonState(button, false);
            }
            this.showNotification('Erreur lors de la synthèse vocale', 'error');
        }
    }

    /**
     * Select appropriate voice based on language and type
     */
    selectVoice(language, voiceType) {
        if (this.voices.length === 0) {
            this.loadVoices();
        }

        // Try to find exact match
        let voice = this.voices.find(v => 
            v.lang === language && 
            v.name.toLowerCase().includes(voiceType)
        );

        // Try language prefix match
        if (!voice) {
            const langPrefix = language.split('-')[0];
            voice = this.voices.find(v => 
                v.lang.startsWith(langPrefix) && 
                v.name.toLowerCase().includes(voiceType)
            );
        }

        // Try any voice with the language
        if (!voice) {
            const langPrefix = language.split('-')[0];
            voice = this.voices.find(v => v.lang.startsWith(langPrefix));
        }

        return voice || this.voices[0];
    }

    /**
     * Stop current speech
     */
    stop() {
        if (this.synth.speaking) {
            this.synth.cancel();
        }
        this.isPlaying = false;
        this.currentUtterance = null;
    }

    /**
     * Pause current speech
     */
    pause() {
        if (this.synth.speaking && !this.synth.paused) {
            this.synth.pause();
        }
    }

    /**
     * Resume paused speech
     */
    resume() {
        if (this.synth.paused) {
            this.synth.resume();
        }
    }

    /**
     * Update button UI state
     */
    updateButtonState(button, state) {
        if (!button) return;

        const icon = button.querySelector('i');
        if (!icon) return;

        if (state === 'loading') {
            icon.className = 'bi bi-hourglass-split';
            button.disabled = true;
            button.title = 'Chargement...';
        } else if (state === true) {
            icon.className = 'bi bi-stop-circle-fill';
            button.disabled = false;
            button.classList.add('btn-danger');
            button.classList.remove('btn-outline-primary', 'btn-primary');
            button.title = 'Arrêter la lecture';
        } else {
            icon.className = 'bi bi-volume-up-fill';
            button.disabled = false;
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-primary');
            button.title = 'Écouter';
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const alertClass = type === 'error' ? 'alert-danger' : type === 'success' ? 'alert-success' : 'alert-info';
        const icon = type === 'error' ? 'x-circle' : type === 'success' ? 'check-circle' : 'info-circle';
        
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            <i class="bi bi-${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 4000);
    }

    /**
     * Get available voices
     */
    getAvailableVoices() {
        return this.voices;
    }

    /**
     * Check if TTS is supported
     */
    isSupported() {
        return 'speechSynthesis' in window;
    }
}

// Create global instance
window.ttsPlayer = new TTSPlayer();

// Show warning if not supported
if (!window.ttsPlayer.isSupported()) {
    console.warn('Speech Synthesis API is not supported in this browser');
}
