import { useEffect, useRef, useState } from 'react';

/* -------------------------------------------------------------- Tela ligada */

/**
 * Mantém a tela acesa enquanto o modo cozinhar está aberto (Screen Wake Lock API).
 * Exige HTTPS (ou localhost). Reativa ao voltar para a aba.
 * Estados: 'on' | 'off' | 'unsupported'.
 */
export function useWakeLock(active) {
  const supported = typeof navigator !== 'undefined' && 'wakeLock' in navigator && window.isSecureContext;
  const [state, setState] = useState(supported ? 'off' : 'unsupported');

  useEffect(() => {
    if (!active || !supported) return undefined;
    let lock = null;
    let cancelled = false;

    const request = async () => {
      try {
        lock = await navigator.wakeLock.request('screen');
        if (cancelled) {
          lock.release().catch(() => {});
          return;
        }
        setState('on');
        lock.addEventListener('release', () => !cancelled && setState('off'));
      } catch (error) {
        setState('off');
      }
    };
    const onVisible = () => {
      if (document.visibilityState === 'visible') request();
    };

    request();
    document.addEventListener('visibilitychange', onVisible);
    return () => {
      cancelled = true;
      document.removeEventListener('visibilitychange', onVisible);
      lock?.release().catch(() => {});
    };
  }, [active, supported]);

  return state;
}

/* -------------------------------------------------------------------- Som */

let audio = null;

/** Precisa ser chamado dentro de um toque (ex.: ao iniciar um timer) para liberar o áudio no iOS. */
export function unlockAudio() {
  try {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return;
    audio = audio || new Ctx();
    if (audio.state === 'suspended') audio.resume();
  } catch (error) {
    audio = null;
  }
}

/** Alarme curto: dois grupos de três bipes. */
export function chime() {
  if (!audio) return;
  const now = audio.currentTime;
  [0, 0.2, 0.4, 0.9, 1.1, 1.3].forEach((offset, i) => {
    const osc = audio.createOscillator();
    const gain = audio.createGain();
    osc.type = 'sine';
    osc.frequency.value = i % 3 === 2 ? 1320 : 990;
    gain.gain.setValueAtTime(0.0001, now + offset);
    gain.gain.exponentialRampToValueAtTime(0.4, now + offset + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.17);
    osc.connect(gain).connect(audio.destination);
    osc.start(now + offset);
    osc.stop(now + offset + 0.2);
  });
}

export function vibrate(pattern) {
  try {
    navigator.vibrate?.(pattern);
  } catch (error) {
    /* ignore */
  }
}

/* -------------------------------------------------------- Leitura em voz alta */

export const ttsSupported = typeof window !== 'undefined' && 'speechSynthesis' in window;

export function speak(text) {
  if (!ttsSupported || !text) return;
  const synth = window.speechSynthesis;
  synth.cancel();
  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang = 'pt-BR';
  const voices = synth.getVoices();
  const voice = voices.find((v) => v.lang?.toLowerCase() === 'pt-br') || voices.find((v) => v.lang?.toLowerCase().startsWith('pt'));
  if (voice) utterance.voice = voice;
  utterance.rate = 1;
  synth.speak(utterance);
}

export function stopSpeaking() {
  if (ttsSupported) window.speechSynthesis.cancel();
}

/* ---------------------------------------------------------- Comandos de voz */

const Recognition = typeof window !== 'undefined' ? window.SpeechRecognition || window.webkitSpeechRecognition : null;
export const voiceSupported = Boolean(Recognition);

/** Interpreta a frase ouvida. Aceita variações ("próximo", "avança", "pode seguir"…). */
export function parseCommand(text) {
  const t = ` ${text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')} `;
  if (/ (proximo|proxima|avanca|avancar|seguinte|segue|seguir|continua|continuar|next) /.test(t)) return 'next';
  if (/ (volta|voltar|anterior|retorna) /.test(t)) return 'prev';
  if (/ (pausa|pausar|pare|parar) /.test(t)) return 'pause';
  if (/ (timer|cronometro|temporizador|iniciar|inicia|comecar|comeca) /.test(t)) return 'timer';
  if (/ (repete|repetir|repita|ler|leia|de novo|como e) /.test(t)) return 'repeat';
  if (/ (ingrediente|ingredientes) /.test(t)) return 'ingredients';
  return null;
}

/**
 * Escuta comandos enquanto `enabled`. Ignora o que for dito enquanto o próprio app está falando.
 * status: 'off' | 'listening' | 'denied' | 'error'
 */
export function useVoiceCommands(enabled, onCommand) {
  const [status, setStatus] = useState('off');
  const [heard, setHeard] = useState('');
  const callback = useRef(onCommand);
  callback.current = onCommand;

  useEffect(() => {
    if (!enabled || !Recognition) {
      setStatus('off');
      return undefined;
    }
    let stopped = false;
    const rec = new Recognition();
    rec.lang = 'pt-BR';
    rec.continuous = true;
    rec.interimResults = false;
    rec.maxAlternatives = 3;

    rec.onresult = (event) => {
      if (ttsSupported && window.speechSynthesis.speaking) return;
      for (let i = event.resultIndex; i < event.results.length; i++) {
        const result = event.results[i];
        if (!result.isFinal) continue;
        const alternatives = Array.from(result).map((alt) => alt.transcript.trim());
        setHeard(alternatives[0]);
        const command = alternatives.map(parseCommand).find(Boolean);
        if (command) callback.current(command);
      }
    };
    rec.onerror = (event) => {
      if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
        stopped = true;
        setStatus('denied');
      }
    };
    rec.onend = () => {
      if (stopped) return;
      window.setTimeout(() => {
        if (stopped) return;
        try {
          rec.start();
        } catch (error) {
          /* já iniciado */
        }
      }, 300);
    };

    try {
      rec.start();
      setStatus('listening');
    } catch (error) {
      setStatus('error');
    }

    return () => {
      stopped = true;
      rec.onend = null;
      try {
        rec.abort();
      } catch (error) {
        /* ignore */
      }
    };
  }, [enabled]);

  return { status, heard };
}
