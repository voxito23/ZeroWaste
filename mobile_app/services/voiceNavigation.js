import AsyncStorage from '@react-native-async-storage/async-storage';

let Speech = null;
try { Speech = require('expo-speech'); } catch { Speech = null; }

const SAFE_LANGUAGE = 'es-MX';
export const VOICE_ENABLED_STORAGE_KEY = 'zerowaste.voice.enabled';
export const VOICE_VOLUME_STORAGE_KEY = 'zerowaste.voice.volume';

class VoiceNavigationService {
  constructor() {
    this.queue = [];
    this.speaking = false;
    this.muted = false;
    this.volume = 1;
    this.rate = 0.96;
    this.lastInstruction = null;
    this.announced = new Set();
    this.hydrationPromise = null;
    this.speechGeneration = 0;
  }

  hydrate() {
    if (!this.hydrationPromise) {
      this.hydrationPromise = Promise.all([
        AsyncStorage.getItem(VOICE_ENABLED_STORAGE_KEY),
        AsyncStorage.getItem(VOICE_VOLUME_STORAGE_KEY),
      ])
        .then(([enabled, volume]) => {
          const parsedVolume = Number(volume);
          if (Number.isFinite(parsedVolume)) this.configure({ volume: parsedVolume });
          return this.setMuted(enabled === 'false');
        })
        .catch(() => this.muted);
    }
    return this.hydrationPromise;
  }

  isMuted() {
    return this.muted;
  }

  isAvailable() {
    return Boolean(Speech?.speak);
  }

  configure({ volume, rate } = {}) {
    if (Number.isFinite(volume)) this.volume = Math.min(1, Math.max(0, volume));
    if (Number.isFinite(rate)) this.rate = Math.min(1.2, Math.max(0.7, rate));
  }

  enqueue({ id, text }, { interrupt = false, remember = true } = {}) {
    const announcement = String(text || '').trim();
    if (!this.isAvailable() || !announcement || this.muted || (id && this.announced.has(String(id)))) return false;
    const item = { id: id ? String(id) : `voice:${Date.now()}`, text: announcement };
    if (id) this.announced.add(String(id));
    if (remember) this.lastInstruction = item;
    if (interrupt) {
      this.queue = [];
      this.speechGeneration += 1;
      void Speech?.stop?.();
      this.speaking = false;
    }
    this.queue.push(item);
    this.flush();
    return true;
  }

  flush() {
    if (this.speaking || this.muted || !this.queue.length) return;
    const current = this.queue.shift();
    const generation = this.speechGeneration;
    this.speaking = true;
    const finish = () => {
      if (generation !== this.speechGeneration) return;
      this.speaking = false;
      this.flush();
    };
    Speech.speak(current.text, {
      language: SAFE_LANGUAGE,
      rate: this.rate,
      volume: this.volume,
      onDone: finish,
      onStopped: finish,
      onError: finish,
    });
  }

  setMuted(muted) {
    this.muted = Boolean(muted);
    if (this.muted) {
      this.queue = [];
      this.speechGeneration += 1;
      this.speaking = false;
      void Speech?.stop?.();
    }
    return this.muted;
  }

  async setEnabled(enabled) {
    this.setMuted(!enabled);
    await AsyncStorage.setItem(VOICE_ENABLED_STORAGE_KEY, String(Boolean(enabled)));
    return Boolean(enabled);
  }

  getVolume() {
    return this.volume;
  }

  async setVolume(volume) {
    this.configure({ volume });
    await AsyncStorage.setItem(VOICE_VOLUME_STORAGE_KEY, String(this.volume));
    return this.volume;
  }

  repeat() {
    if (!this.lastInstruction || this.muted) return false;
    return this.enqueue(
      { id: `repeat:${Date.now()}`, text: this.lastInstruction.text },
      { interrupt: true, remember: false },
    );
  }

  cancelObsolete() {
    this.queue = [];
    this.speechGeneration += 1;
    this.speaking = false;
    void Speech?.stop?.();
  }

  resetRoute() {
    this.cancelObsolete();
    this.announced.clear();
    this.lastInstruction = null;
  }
}

export const voiceNavigation = new VoiceNavigationService();
export { SAFE_LANGUAGE as VOICE_NAVIGATION_LANGUAGE };
