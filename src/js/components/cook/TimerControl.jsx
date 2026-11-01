import { clock } from '../../lib/format';
import Icon from '../Icon';

function Ring({ progress, done }) {
  const r = 26;
  const c = 2 * Math.PI * r;
  return (
    <svg viewBox="0 0 64 64" className="h-16 w-16 -rotate-90" aria-hidden="true">
      <circle cx="32" cy="32" r={r} fill="none" strokeWidth="6" className="stroke-ink/10 dark:stroke-white/15" />
      <circle
        cx="32"
        cy="32"
        r={r}
        fill="none"
        strokeWidth="6"
        strokeLinecap="round"
        strokeDasharray={c}
        strokeDashoffset={c * (1 - progress)}
        className={`transition-[stroke-dashoffset] duration-300 ${done ? 'stroke-mint-500' : 'stroke-iris-500'}`}
      />
    </svg>
  );
}

/**
 * Timer do passo. Ocioso → botão grande "Iniciar timer"; rodando → anel + contagem + pausar;
 * terminado → aviso verde. O timer segue rodando se a pessoa avançar de passo (aparece no dock).
 */
export default function TimerControl({ step, timer, onStart, onPause, onReset }) {
  if (!step.seconds) return null;

  if (!timer) {
    return (
      <button type="button" onClick={onStart} className="btn btn-accent h-16 w-full rounded-3xl text-lg">
        <Icon name="play" className="h-5 w-5" fill="currentColor" stroke={1} />
        Iniciar timer · {clock(step.seconds)}
      </button>
    );
  }

  const progress = timer.done ? 1 : 1 - timer.remaining / timer.total;

  return (
    <div
      className={`flex items-center gap-4 rounded-3xl p-3 pr-4 ring-1 transition ${
        timer.done ? 'bg-mint-100 ring-mint-300 dark:bg-mint-500/20 dark:ring-mint-500/40' : 'bg-white/80 ring-white dark:bg-white/[.07] dark:ring-white/10'
      }`}
      role="timer"
      aria-live={timer.done ? 'assertive' : 'off'}
    >
      <div className={`relative ${timer.done ? 'animate-ring' : ''}`}>
        <Ring progress={progress} done={timer.done} />
        <Icon name={timer.done ? 'check' : 'timer'} className={`absolute inset-0 m-auto h-6 w-6 ${timer.done ? 'text-mint-700 dark:text-mint-300' : 'text-iris-500'}`} stroke={2.2} />
      </div>
      <div className="min-w-0 flex-1">
        <p className="text-xs font-bold uppercase tracking-wider text-ink-mute dark:text-white/60">{timer.done ? 'Tempo concluído' : timer.running ? 'Timer rodando' : 'Timer pausado'}</p>
        <p className="text-4xl font-extrabold tabular-nums tracking-tight">{timer.done ? 'Pronto!' : clock(timer.remaining)}</p>
      </div>
      {!timer.done && (
        <button
          type="button"
          onClick={timer.running ? onPause : onStart}
          className="btn h-14 w-14 shrink-0 rounded-full bg-ink p-0 text-white dark:bg-white dark:text-ink"
          aria-label={timer.running ? 'Pausar timer' : 'Continuar timer'}
        >
          <Icon name={timer.running ? 'pause' : 'play'} className="h-6 w-6" fill="currentColor" stroke={1} />
        </button>
      )}
      <button type="button" onClick={onReset} className="btn h-14 w-14 shrink-0 rounded-full bg-white/70 p-0 ring-1 ring-ink/10 dark:bg-white/10 dark:ring-white/10" aria-label="Zerar timer">
        <Icon name="rotate" className="h-5 w-5" />
      </button>
    </div>
  );
}
