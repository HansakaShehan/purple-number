// RequestManager - API communication
class RequestManager {
  async postJSON(url, data = {}) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    
    const json = await res.json();
    
    if (!res.ok) {
      throw json;
    }
    
    return json;
  }
}

// AudioManager - Web Audio API synthesis
class AudioManager {
  constructor() {
    this.audioContext = null;
    this.masterGain = null;
    this.musicGain = null;
    this.soundGain = null;
    this.isMuted = localStorage.getItem('purple-guess-muted') === 'true';
    this.musicStarted = false;
  }

  init() {
    if (this.audioContext) return;
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;

    this.audioContext = new AudioCtx();
    this.masterGain = this.audioContext.createGain();
    this.musicGain = this.audioContext.createGain();
    this.soundGain = this.audioContext.createGain();

    this.masterGain.gain.value = this.isMuted ? 0 : 1;
    this.musicGain.gain.value = 0.2;
    this.soundGain.gain.value = 0.85;

    this.musicGain.connect(this.masterGain);
    this.soundGain.connect(this.masterGain);
    this.masterGain.connect(this.audioContext.destination);

    this.audioContext.resume().catch(() => {});
    this.startBackgroundLoop();
  }

  setMuted(muted) {
    this.isMuted = Boolean(muted);
    localStorage.setItem('purple-guess-muted', String(this.isMuted));
    if (!this.audioContext) return;
    const now = this.audioContext.currentTime;
    this.masterGain.gain.cancelScheduledValues(now);
    this.masterGain.gain.setTargetAtTime(this.isMuted ? 0 : 1, now, 0.02);
  }

  toggleMute() {
    this.init();
    this.setMuted(!this.isMuted);
    return this.isMuted;
  }

  startBackgroundLoop() {
    if (!this.audioContext || this.musicStarted) return;
    this.musicStarted = true;

    const now = this.audioContext.currentTime;
    const baseFreq = 110;

    const osc1 = this.audioContext.createOscillator();
    osc1.type = 'sine';
    osc1.frequency.value = baseFreq;
    const gain1 = this.audioContext.createGain();
    gain1.gain.value = 0.06;
    osc1.connect(gain1).connect(this.musicGain);
    osc1.start(now);

    const osc2 = this.audioContext.createOscillator();
    osc2.type = 'triangle';
    osc2.frequency.value = baseFreq * 1.5;
    const gain2 = this.audioContext.createGain();
    gain2.gain.value = 0.04;
    osc2.connect(gain2).connect(this.musicGain);
    osc2.start(now);

    const lfo = this.audioContext.createOscillator();
    lfo.type = 'sine';
    lfo.frequency.value = 0.085;
    const lfoGain = this.audioContext.createGain();
    lfoGain.gain.value = 0.12;
    lfo.connect(lfoGain).connect(this.musicGain.gain);
    lfo.start(now);

    this.scheduleMelody();
  }

  scheduleMelody() {
    const now = this.audioContext.currentTime;
    const baseFreq = 110;
    const melody = [0, 2, 4, 7, 9, 7, 4, 2];
    const stepSeconds = 0.55;

    melody.forEach((step, index) => {
      const tone = this.audioContext.createOscillator();
      const amp = this.audioContext.createGain();
      tone.type = 'square';
      tone.frequency.value = baseFreq * Math.pow(2, (step + 12) / 12);

      amp.gain.setValueAtTime(0.0001, now + index * stepSeconds);
      amp.gain.exponentialRampToValueAtTime(0.08, now + index * stepSeconds + 0.05);
      amp.gain.exponentialRampToValueAtTime(0.0001, now + (index + 1) * stepSeconds);

      tone.connect(amp).connect(this.musicGain);
      tone.start(now + index * stepSeconds);
      tone.stop(now + (index + 1) * stepSeconds);
    });

    window.setTimeout(() => this.scheduleMelody(), melody.length * stepSeconds * 1000);
  }

  playSound(type) {
    if (!this.audioContext) return;
    const ctx = this.audioContext;
    const now = ctx.currentTime;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain).connect(this.soundGain);
    gain.gain.setValueAtTime(0.0001, now);

    switch (type) {
      case 'start':
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(220, now);
        gain.gain.exponentialRampToValueAtTime(0.28, now + 0.04);
        osc.frequency.exponentialRampToValueAtTime(440, now + 0.32);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.34);
        break;
      case 'click':
        osc.type = 'square';
        osc.frequency.setValueAtTime(340, now);
        gain.gain.exponentialRampToValueAtTime(0.16, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.10);
        break;
      case 'success':
        osc.type = 'sine';
        osc.frequency.setValueAtTime(330, now);
        gain.gain.exponentialRampToValueAtTime(0.3, now + 0.02);
        osc.frequency.exponentialRampToValueAtTime(660, now + 0.26);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.42);
        break;
      case 'fail':
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(150, now);
        gain.gain.exponentialRampToValueAtTime(0.24, now + 0.02);
        osc.frequency.exponentialRampToValueAtTime(84, now + 0.28);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.48);
        break;
      case 'timeout':
        osc.type = 'square';
        osc.frequency.setValueAtTime(180, now);
        gain.gain.exponentialRampToValueAtTime(0.26, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28);
        break;
      case 'tick':
        osc.type = 'square';
        osc.frequency.setValueAtTime(660, now);
        gain.gain.exponentialRampToValueAtTime(0.18, now + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.08);
        break;
      default:
        osc.type = 'sine';
        osc.frequency.setValueAtTime(260, now);
        gain.gain.exponentialRampToValueAtTime(0.18, now + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.18);
        break;
    }

    osc.start(now);
    osc.stop(now + 0.55);
  }
}

// Initialize global managers when app loads
window.addEventListener('DOMContentLoaded', () => {
  window.requestManager = new RequestManager();
  window.audioManager = new AudioManager();
});
