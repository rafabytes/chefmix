import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Vários timers ao mesmo tempo, um por passo. Continuam correndo quando a pessoa troca de passo.
 * Cada timer: { total, remaining, endAt, running, done }.
 * Usa o relógio (endAt) e não a contagem de ticks, então não atrasa com a aba em segundo plano.
 */
export function useTimers(onDone) {
  const [timers, setTimers] = useState({});
  const timersRef = useRef(timers);
  timersRef.current = timers;
  const doneRef = useRef(onDone);
  doneRef.current = onDone;

  const anyRunning = Object.values(timers).some((t) => t.running);

  useEffect(() => {
    if (!anyRunning) return undefined;
    const id = window.setInterval(() => {
      const now = Date.now();
      // Calculado fora do updater: com o batching do React 18 o updater só roda na renderização.
      const finished = Object.entries(timersRef.current)
        .filter(([, t]) => t.running && t.endAt <= now)
        .map(([key]) => Number(key));

      setTimers((current) => {
        const next = { ...current };
        Object.entries(current).forEach(([key, t]) => {
          if (!t.running) return;
          next[key] =
            t.endAt <= now
              ? { ...t, remaining: 0, running: false, done: true, endAt: null }
              : { ...t, remaining: Math.max(0, (t.endAt - now) / 1000) };
        });
        return next;
      });
      finished.forEach((key) => doneRef.current?.(key));
    }, 250);
    return () => window.clearInterval(id);
  }, [anyRunning]);

  const start = useCallback((key, total) => {
    setTimers((current) => {
      const t = current[key];
      const remaining = t && !t.done && t.remaining > 0 ? t.remaining : total;
      return { ...current, [key]: { total, remaining, endAt: Date.now() + remaining * 1000, running: true, done: false } };
    });
  }, []);

  const pause = useCallback((key) => {
    setTimers((current) => {
      const t = current[key];
      if (!t || !t.running) return current;
      return { ...current, [key]: { ...t, running: false, endAt: null, remaining: Math.max(0, (t.endAt - Date.now()) / 1000) } };
    });
  }, []);

  const reset = useCallback((key) => {
    setTimers((current) => {
      const next = { ...current };
      delete next[key];
      return next;
    });
  }, []);

  return { timers, start, pause, reset, anyRunning };
}
