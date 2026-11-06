/**
 * Sistema Avançado de Áudio e Vibração
 * Utiliza Web Audio API para síntese procedural de 8 sons autônomos
 * + Suporte a arquivo de áudio personalizado pelo usuário (MP3/WAV/OGG)
 */
const SoundSystem = {
    audioCtx: null,
    isPlaying: false,
    intervalId: null,
    activeOscillators: [],
    customAudioElement: null,
    previewTimeoutId: null,

    initContext() {
        if (!this.audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContext();
        }
        if (this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
    },

    /**
     * Limpa osciladores ativos
     */
    clearOscillators() {
        this.activeOscillators.forEach(osc => {
            try { osc.stop(); osc.disconnect(); } catch (e) {}
        });
        this.activeOscillators = [];
    },

    /**
     * Inicia o alarme contínuo conforme o tipo de som selecionado
     */
    startAlarm(soundType = 'sirene') {
        this.initContext();
        this.stopSound();
        this.isPlaying = true;

        switch (soundType) {
            case 'sirene':
                this.playSiren();
                break;
            case 'beep':
                this.playContinuousBeep();
                break;
            case 'despertador':
                this.playTwinBell();
                break;
            case 'radar':
                this.playSonarRadar();
                break;
            case 'buzina':
                this.playAirHorn();
                break;
            case 'melodia':
                this.playMelody();
                break;
            case 'sino':
                this.playChimeBell();
                break;
            case 'urgente':
                this.playNuclearAlarm();
                break;
            case 'custom':
                this.playCustomAudio();
                break;
            default:
                this.playSiren();
        }
    },

    /**
     * Toca uma prévia rápida de 1.5s do som selecionado
     */
    preview(soundType = 'sirene', durationMs = 1500) {
        this.initContext();
        this.stopAll();
        if (this.previewTimeoutId) {
            clearTimeout(this.previewTimeoutId);
        }

        this.startAlarm(soundType);

        this.previewTimeoutId = setTimeout(() => {
            this.stopSound();
        }, durationMs);
    },

    /**
     * 1. Sirene de Emergência / Polícia (Sweep modulado contínuo)
     */
    playSiren() {
        const loop = () => {
            if (!this.isPlaying) return;
            const now = this.audioCtx.currentTime;
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(650, now);
            osc.frequency.linearRampToValueAtTime(1250, now + 0.45);
            osc.frequency.linearRampToValueAtTime(650, now + 0.9);

            gain.gain.setValueAtTime(0.35, now);
            gain.gain.linearRampToValueAtTime(0.35, now + 0.85);
            gain.gain.linearRampToValueAtTime(0.01, now + 0.9);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start(now);
            osc.stop(now + 0.9);
            this.activeOscillators.push(osc);

            this.intervalId = setTimeout(loop, 920);
        };
        loop();
    },

    /**
     * 2. Bip Digital Rápido (Relógio digital / Pulsos eletrônicos)
     */
    playContinuousBeep() {
        const loop = () => {
            if (!this.isPlaying) return;
            const now = this.audioCtx.currentTime;
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'square';
            osc.frequency.setValueAtTime(1050, now);

            gain.gain.setValueAtTime(0.28, now);
            gain.gain.linearRampToValueAtTime(0.28, now + 0.12);
            gain.gain.linearRampToValueAtTime(0.01, now + 0.15);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start(now);
            osc.stop(now + 0.15);
            this.activeOscillators.push(osc);

            this.intervalId = setTimeout(loop, 220);
        };
        loop();
    },

    /**
     * 3. Despertador Clássico (Twin-Bell Mechanical Alarm)
     */
    playTwinBell() {
        const loop = () => {
            if (!this.isPlaying) return;
            const now = this.audioCtx.currentTime;

            // Dois sinos com frequências metálicas alternadas
            const freqs = [1850, 2300, 1850, 2300, 1850, 2300];
            freqs.forEach((f, idx) => {
                const strikeTime = now + (idx * 0.07);
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'triangle';
                osc.frequency.setValueAtTime(f, strikeTime);

                gain.gain.setValueAtTime(0.35, strikeTime);
                gain.gain.exponentialRampToValueAtTime(0.001, strikeTime + 0.09);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);

                osc.start(strikeTime);
                osc.stop(strikeTime + 0.09);
                this.activeOscillators.push(osc);
            });

            this.intervalId = setTimeout(loop, 550);
        };
        loop();
    },

    /**
     * 4. Sonar / Radar Submarino (Ping metálico com decaimento exponencial)
     */
    playSonarRadar() {
        const loop = () => {
            if (!this.isPlaying) return;
            const now = this.audioCtx.currentTime;
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(1400, now);
            osc.frequency.exponentialRampToValueAtTime(700, now + 0.6);

            gain.gain.setValueAtTime(0.4, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.7);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start(now);
            osc.stop(now + 0.7);
            this.activeOscillators.push(osc);

            this.intervalId = setTimeout(loop, 900);
        };
        loop();
    },

    /**
     * 5. Buzina de Trânsito / Trem (Acorde harmônico potente)
     */
    playAirHorn() {
        const loop = () => {
            if (!this.isPlaying) return;
            const now = this.audioCtx.currentTime;

            // Dois tons graves combinados (440Hz + 554Hz)
            [440, 554].forEach(f => {
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(f, now);

                gain.gain.setValueAtTime(0.22, now);
                gain.gain.linearRampToValueAtTime(0.22, now + 0.45);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.55);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);

                osc.start(now);
                osc.stop(now + 0.55);
                this.activeOscillators.push(osc);
            });

            this.intervalId = setTimeout(loop, 750);
        };
        loop();
    },

    /**
     * 6. Melodia Musical Alegre (Arpejo rítmico de despertar)
     */
    playMelody() {
        const notes = [523.25, 659.25, 783.99, 1046.50, 880.00, 783.99, 659.25, 523.25]; // C5, E5, G5, C6, A5, G5, E5, C5
        let step = 0;

        const loop = () => {
            if (!this.isPlaying) return;
            const now = this.audioCtx.currentTime;
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'triangle';
            osc.frequency.setValueAtTime(notes[step % notes.length], now);
            step++;

            gain.gain.setValueAtTime(0.35, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.22);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start(now);
            osc.stop(now + 0.22);
            this.activeOscillators.push(osc);

            this.intervalId = setTimeout(loop, 240);
        };
        loop();
    },

    /**
     * 7. Sino de Alerta / Carrilhão (Harmônicos ressonantes)
     */
    playChimeBell() {
        const loop = () => {
            if (!this.isPlaying) return;
            const now = this.audioCtx.currentTime;

            // Harmônicos brilhantes de sino
            [587.33, 1174.66, 1761.99].forEach((f, idx) => {
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(f, now);

                const volume = 0.3 / (idx + 1);
                gain.gain.setValueAtTime(volume, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.8);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);

                osc.start(now);
                osc.stop(now + 0.8);
                this.activeOscillators.push(osc);
            });

            this.intervalId = setTimeout(loop, 950);
        };
        loop();
    },

    /**
     * 8. Alarme Urgente Nuclear / Alta Tensão (Pulso agressivo alternado)
     */
    playNuclearAlarm() {
        const loop = () => {
            if (!this.isPlaying) return;
            const now = this.audioCtx.currentTime;
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(800, now);
            osc.frequency.setValueAtTime(1500, now + 0.15);
            osc.frequency.setValueAtTime(800, now + 0.3);
            osc.frequency.setValueAtTime(1500, now + 0.45);

            gain.gain.setValueAtTime(0.38, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.58);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start(now);
            osc.stop(now + 0.58);
            this.activeOscillators.push(osc);

            this.intervalId = setTimeout(loop, 620);
        };
        loop();
    },

    /**
     * 9. Áudio Personalizado (Carregado pelo usuário)
     */
    playCustomAudio() {
        const customData = localStorage.getItem('app_custom_sound');
        if (!customData) {
            // Se o usuário não enviou arquivo ainda, toca a sirene como fallback seguro
            this.playSiren();
            return;
        }

        if (!this.customAudioElement) {
            this.customAudioElement = new Audio();
        }
        this.customAudioElement.src = customData;
        this.customAudioElement.loop = true;
        this.customAudioElement.play().catch(e => {
            console.warn('Erro ao tocar áudio personalizado, usando fallback', e);
            this.playSiren();
        });
    },

    /**
     * Salva arquivo de som do usuário
     */
    saveCustomAudioFile(file, callback) {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                localStorage.setItem('app_custom_sound', e.target.result);
                localStorage.setItem('app_custom_sound_name', file.name);
                if (typeof callback === 'function') {
                    callback(true, file.name);
                }
            } catch (err) {
                if (typeof callback === 'function') {
                    callback(false, 'Arquivo muito grande para salvar em memória local.');
                }
            }
        };
        reader.readAsDataURL(file);
    },

    /**
     * Interrompe todos os sons em execução
     */
    stopSound() {
        this.isPlaying = false;
        if (this.intervalId) {
            clearTimeout(this.intervalId);
            this.intervalId = null;
        }
        if (this.previewTimeoutId) {
            clearTimeout(this.previewTimeoutId);
            this.previewTimeoutId = null;
        }
        this.clearOscillators();

        if (this.customAudioElement) {
            this.customAudioElement.pause();
            this.customAudioElement.currentTime = 0;
        }
    },

    /**
     * Vibração contínua
     */
    startVibration() {
        if ('vibrate' in navigator) {
            navigator.vibrate([500, 250, 500, 250, 800, 300]);
            this.vibeInterval = setInterval(() => {
                navigator.vibrate([500, 250, 500, 250, 800, 300]);
            }, 2600);
        }
    },

    /**
     * Para a vibração
     */
    stopVibration() {
        if ('vibrate' in navigator) {
            navigator.vibrate(0);
        }
        if (this.vibeInterval) {
            clearInterval(this.vibeInterval);
            this.vibeInterval = null;
        }
    },

    /**
     * Dispara alarme completo (Som + Vibração)
     */
    trigger(tipoAlarme = 'ambos', somAlarme = 'sirene') {
        if (tipoAlarme === 'som' || tipoAlarme === 'ambos') {
            this.startAlarm(somAlarme);
        }
        if (tipoAlarme === 'vibracao' || tipoAlarme === 'ambos') {
            this.startVibration();
        }
    },

    /**
     * Encerra tudo
     */
    stopAll() {
        this.stopSound();
        this.stopVibration();
    }
};
