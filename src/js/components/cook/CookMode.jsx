import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { clock, minutes, scaleQty, settingsSummary, stepSpeech } from '../../lib/format';
import { load, remove, save } from '../../lib/storage';
import { dialogOpen } from '../../lib/useDialog';
import { lockScroll, unlockScroll } from '../../lib/scrollLock';
import Icon from '../Icon';
import FavoriteButton from '../FavoriteButton';
import FollowButton from '../FollowButton';
import StepView from './StepView';
import MisePlace from './MisePlace';
import IngredientText from '../IngredientText';
import Sheet from './Sheet';
import Onboarding from './Onboarding';
import { useTimers } from './useTimers';
import { chime, speak, stopSpeaking, ttsSupported, unlockAudio, useVoiceCommands, useWakeLock, vibrate, voiceSupported } from './device';

const PREFS_KEY = 'cmx:prefs';
const DEFAULT_PREFS = { theme: 'light', scale: 1, speak: false, voice: false, sound: true };

export const progressKey = (id) => `cmx:progress:${id}`;

/** Índice da etapa de mise en place (antes do passo 1). */
export const MISE = -1;

const MISE_SPEECH = 'Mise en place. Antes de ligar o fogo, separe os utensílios e deixe cada ingrediente medido e preparado. Diga próximo quando estiver tudo pronto.';

function usePrefs() {
  const [prefs, setPrefs] = useState(() => ({ ...DEFAULT_PREFS, ...load(PREFS_KEY, {}) }));
  const update = (patch) =>
    setPrefs((current) => {
      const next = { ...current, ...patch };
      save(PREFS_KEY, next);
      return next;
    });
  return [prefs, update];
}

/* -------------------------------------------------------------- Peças menores */

function Toggle({ label, hint, checked, onChange, disabled }) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      disabled={disabled}
      onClick={() => onChange(!checked)}
      className="flex w-full items-center gap-4 rounded-3xl bg-white/70 p-4 text-left ring-1 ring-white transition disabled:opacity-50 dark:bg-white/[.06] dark:ring-white/10"
    >
      <span className="flex-1">
        <span className="block font-bold">{label}</span>
        {hint && <span className="mt-0.5 block text-sm text-ink-mute dark:text-white/55">{hint}</span>}
      </span>
      <span className={`relative h-8 w-14 shrink-0 rounded-full transition ${checked ? 'bg-iris-500' : 'bg-ink/15 dark:bg-white/20'}`}>
        <span className={`absolute top-1 h-6 w-6 rounded-full bg-white shadow transition-all ${checked ? 'left-7' : 'left-1'}`} />
      </span>
    </button>
  );
}

function Segmented({ label, value, options, onChange }) {
  return (
    <div>
      <p className="mb-2 text-sm font-bold">{label}</p>
      <div className="flex rounded-full bg-ink/5 p-1 dark:bg-white/10">
        {options.map((option) => (
          <button
            key={option.value}
            type="button"
            aria-pressed={value === option.value}
            onClick={() => onChange(option.value)}
            className={`flex-1 rounded-full px-3 py-2.5 font-bold transition ${value === option.value ? 'bg-white text-ink shadow-soft' : 'text-ink-mute dark:text-white/60'}`}
          >
            {option.label}
          </button>
        ))}
      </div>
    </div>
  );
}

function Status({ tone, children }) {
  const dot = { mint: 'bg-mint-500', iris: 'bg-iris-500 animate-pulse', rose: 'bg-rose-500' }[tone];
  return (
    <span className="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold text-ink-soft ring-1 ring-white dark:bg-white/10 dark:text-white/70 dark:ring-white/10">
      <span className={`h-2 w-2 rounded-full ${dot}`} />
      {children}
    </span>
  );
}

/* -------------------------------------------------------------- Modo cozinhar */

/**
 * Modo cozinhar em tela cheia: começa pela mise en place e segue um passo por tela,
 * com timers, tela sempre ligada, voz e gestos.
 * Abre por cima da página (portal) e usa o histórico: o "voltar" do celular fecha o modo.
 */
export default function CookMode({ recipe, servings, startAt = 0, prepChecked, onTogglePrep, onClose }) {
  const steps = recipe.steps;
  const total = steps.length;
  const factor = servings / recipe.servings;

  const [index, setIndex] = useState(Math.min(Math.max(MISE, startAt), total - 1));
  const [direction, setDirection] = useState(1);
  const [checked, setChecked] = useState(() => new Set());
  const [sheet, setSheet] = useState(null);
  const [prefs, setPrefs] = usePrefs();
  const [onboarding, setOnboarding] = useState(() => !load('cmx:onboarded:2'));
  const [alerts, setAlerts] = useState([]);

  const finished = index >= total;
  const mise = index === MISE;
  const step = steps[Math.min(Math.max(index, 0), total - 1)];
  const mainRef = useRef(null);
  const rootRef = useRef(null);
  const touch = useRef(null);

  const wake = useWakeLock(true);

  const timers = useTimers((key) => {
    if (prefs.sound) chime();
    vibrate([300, 120, 300, 120, 600]);
    setAlerts((list) => [...list.filter((k) => k !== key), key]);
    if (prefs.speak) speak(`Tempo do passo ${key + 1} concluído. ${steps[key]?.action || ''}`);
  });

  /* ------------------------------------------------------------ navegação */

  // `to` pode ser um índice ou uma função do índice atual (toques rápidos seguidos não usam valor velho).
  const go = useCallback(
    (to) => {
      setIndex((current) => {
        const next = Math.max(MISE, Math.min(total, typeof to === 'function' ? to(current) : to));
        if (next !== current) {
          setDirection(next > current ? 1 : -1);
          vibrate(8);
        }
        return next;
      });
    },
    [total],
  );
  const next = () => go((i) => i + 1);
  const prev = () => go((i) => i - 1);

  const startTimer = () => {
    if (mise || finished || !step.seconds) return;
    unlockAudio();
    timers.start(index, step.seconds);
    setAlerts((list) => list.filter((k) => k !== index));
  };

  // Fechar: pelo botão volta no histórico (a entrada que o modo criou); pelo "voltar" do celular, direto.
  const onCloseRef = useRef(onClose);
  onCloseRef.current = onClose;
  const stateRef = useRef({ index, finished });
  stateRef.current = { index, finished };

  useEffect(() => {
    window.history.pushState({ cmxCook: true }, '');
    const onPop = () => onCloseRef.current(stateRef.current.index, stateRef.current.finished);
    window.addEventListener('popstate', onPop);
    return () => window.removeEventListener('popstate', onPop);
  }, []);

  const requestClose = () => {
    if (timers.anyRunning && !window.confirm('Há um timer rodando. Sair do modo cozinhar mesmo assim?')) return;
    stopSpeaking();
    if (window.history.state?.cmxCook) window.history.back();
    else onClose(index, finished);
  };

  /* --------------------------------------------------------------- efeitos */

  // Trava a página por baixo e esconde cabeçalho/abas.
  useEffect(() => {
    const html = document.documentElement;
    html.classList.add('cmx-cooking');
    lockScroll();
    rootRef.current?.focus({ preventScroll: true });
    return () => {
      html.classList.remove('cmx-cooking');
      unlockScroll();
      stopSpeaking();
    };
  }, []);

  // Progresso salvo no navegador (para "Continuar do passo N") + rolagem para o topo + leitura em voz alta.
  useEffect(() => {
    if (finished) remove(progressKey(recipe.id));
    else if (!mise) save(progressKey(recipe.id), { step: index, servings });
    mainRef.current?.scrollTo({ top: 0 });
    setAlerts((list) => list.filter((k) => k !== index));
    if (prefs.speak && !finished) speak(mise ? MISE_SPEECH : stepSpeech(step, factor, index + 1));
  }, [index]);

  useEffect(() => {
    if (!prefs.speak) stopSpeaking();
  }, [prefs.speak]);

  // Teclado: → / espaço avança, ← volta, T timer, Esc sai.
  useEffect(() => {
    const onKey = (event) => {
      if (dialogOpen() || event.target.closest?.('input, textarea')) return;
      if (['ArrowRight', 'PageDown', ' '].includes(event.key)) {
        event.preventDefault();
        next();
      } else if (['ArrowLeft', 'PageUp'].includes(event.key)) {
        event.preventDefault();
        prev();
      } else if (event.key === 't' || event.key === 'T') {
        startTimer();
      } else if (event.key === 'Escape') {
        requestClose();
      }
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  });

  // Comandos de voz.
  const voice = useVoiceCommands(prefs.voice && !onboarding, (command) => {
    if (command === 'next') next();
    else if (command === 'prev') prev();
    else if (command === 'repeat' && !finished) speak(mise ? MISE_SPEECH : stepSpeech(step, factor, index + 1));
    else if (command === 'timer') startTimer();
    else if (command === 'pause') timers.pause(index);
    else if (command === 'ingredients') setSheet('ingredients');
  });

  /* ------------------------------------------------------------- derivados */

  const remainingMin = useMemo(() => {
    const seconds = steps.slice(Math.max(0, index)).reduce((sum, s) => sum + (s.seconds || 60), 0);
    return Math.ceil(seconds / 60);
  }, [index, steps]);

  const toggleStepIngredient = (key) =>
    setChecked((current) => {
      const nextSet = new Set(current);
      if (nextSet.has(key)) nextSet.delete(key);
      else nextSet.add(key);
      return nextSet;
    });

  const dock = Object.entries(timers.timers)
    .map(([key, t]) => ({ key: Number(key), ...t }))
    .filter((t) => t.key !== index && (t.running || t.done || t.remaining > 0));

  const onTouchStart = (event) => {
    const t = event.touches[0];
    touch.current = { x: t.clientX, y: t.clientY, time: Date.now() };
  };
  const onTouchEnd = (event) => {
    if (!touch.current) return;
    const t = event.changedTouches[0];
    const dx = t.clientX - touch.current.x;
    const dy = t.clientY - touch.current.y;
    if (Math.abs(dx) > 70 && Math.abs(dy) < 60 && Date.now() - touch.current.time < 700) {
      if (dx < 0) next();
      else prev();
    }
    touch.current = null;
  };

  const nextStep = steps[index + 1];

  /* ---------------------------------------------------------------- render */

  return (
    <div
      ref={rootRef}
      tabIndex={-1}
      data-theme={prefs.theme}
      role="dialog"
      aria-modal="true"
      aria-label={`Modo cozinhar: ${recipe.title}`}
      className="fixed inset-0 z-[100] flex flex-col bg-mist text-ink outline-none dark:bg-[#0d0d14] dark:text-white"
    >
      {/* Fundo */}
      <div className="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <span className="blob left-[-20%] top-[-15%] h-[60vmax] w-[60vmax] bg-iris-200 dark:bg-iris-700/30" />
        <span className="blob bottom-[-25%] right-[-20%] h-[55vmax] w-[55vmax] bg-mint-100 [animation-delay:-8s] dark:bg-mint-700/20" />
      </div>

      <p className="sr-only" aria-live="polite">
        {finished ? 'Receita concluída.' : mise ? 'Mise en place: separe e prepare os ingredientes.' : `Passo ${index + 1} de ${total}: ${step.action}`}
      </p>

      {/* Topo: sair · onde estou · ingredientes · ajustes */}
      <header className="relative px-3 pt-[max(.75rem,env(safe-area-inset-top))] sm:px-6">
        <div className="mx-auto flex max-w-5xl items-center gap-2">
          <button type="button" onClick={requestClose} className="btn btn-glass h-12 w-12 shrink-0 p-0 dark:bg-white/10 dark:text-white dark:ring-white/10" aria-label="Sair do modo cozinhar">
            <Icon name="x" className="h-6 w-6" />
          </button>
          <button type="button" onClick={() => setSheet('steps')} className="min-w-0 flex-1 rounded-2xl px-2 py-1 text-left transition hover:bg-white/50 dark:hover:bg-white/5" aria-label="Ver todos os passos">
            <span className="block truncate text-sm font-semibold text-ink-mute dark:text-white/55">{recipe.title}</span>
            <span className="flex items-center gap-1 whitespace-nowrap text-base font-extrabold sm:text-lg">
              {finished ? 'Concluída' : mise ? 'Mise en place' : `Passo ${index + 1} de ${total}`}
              {!finished && !mise && <span className="truncate text-sm font-semibold text-ink-mute dark:text-white/50">· ~{minutes(remainingMin)}</span>}
              <Icon name="chevron-down" className="h-4 w-4 shrink-0 text-ink-mute" />
            </span>
          </button>
          <button type="button" onClick={() => setSheet('ingredients')} className="btn btn-glass h-12 w-12 shrink-0 p-0 dark:bg-white/10 dark:text-white dark:ring-white/10" aria-label="Ingredientes">
            <Icon name="list" className="h-6 w-6" />
          </button>
          <button type="button" onClick={() => setSheet('settings')} className="btn btn-glass h-12 w-12 shrink-0 p-0 dark:bg-white/10 dark:text-white dark:ring-white/10" aria-label="Ajustes">
            <Icon name="sliders" className="h-6 w-6" />
          </button>
        </div>

        {/* Barra de progresso segmentada (toque = lista de passos) */}
        <button type="button" onClick={() => setSheet('steps')} className="mx-auto mt-3 flex w-full max-w-5xl gap-1 py-1" aria-label={mise ? 'Progresso: mise en place' : `Progresso: passo ${Math.min(index + 1, total)} de ${total}`}>
          <span className={`h-1.5 w-5 shrink-0 rounded-full transition-colors ${mise ? 'bg-gradient-to-r from-iris-500 to-mint-400' : 'bg-ink dark:bg-white'}`} title="Mise en place" />
          {steps.map((s, i) => (
            <span
              key={i}
              className={`h-1.5 flex-1 rounded-full transition-colors ${
                i < index ? 'bg-ink dark:bg-white' : i === index ? 'bg-gradient-to-r from-iris-500 to-mint-400' : timers.timers[i]?.done ? 'bg-mint-400' : 'bg-ink/10 dark:bg-white/15'
              }`}
            />
          ))}
        </button>

        {(wake === 'on' || voice.status !== 'off') && (
          <div className="mx-auto mt-2 flex max-w-5xl flex-wrap gap-1.5">
            {wake === 'on' && <Status tone="mint">Tela sempre ligada</Status>}
            {voice.status === 'listening' && <Status tone="iris">Ouvindo · diga “próximo”</Status>}
            {voice.status === 'denied' && <Status tone="rose">Microfone bloqueado</Status>}
          </div>
        )}
      </header>

      {/* Avisos de timer concluído (de qualquer passo) */}
      {alerts.length > 0 && (
        <div className="relative z-10 mx-auto mt-2 flex w-full max-w-5xl flex-col gap-2 px-3 sm:px-6">
          {alerts.map((key) => (
            <div key={key} className="flex animate-sheet-in items-center gap-3 rounded-3xl bg-mint-100 p-2 pl-4 text-ink ring-1 ring-mint-300" role="alert">
              <Icon name="timer" className="h-6 w-6 shrink-0 text-mint-700" stroke={2.2} />
              <p className="min-w-0 flex-1 font-bold leading-tight">
                Tempo concluído · passo {key + 1}
                <span className="block truncate text-sm font-semibold text-ink/70">{steps[key]?.action}</span>
              </p>
              {key !== index && (
                <button type="button" className="btn btn-primary h-11 px-4" onClick={() => go(key)}>
                  Ver
                </button>
              )}
              <button type="button" className="icon-btn" onClick={() => setAlerts((list) => list.filter((k) => k !== key))} aria-label="Dispensar aviso">
                <Icon name="x" />
              </button>
            </div>
          ))}
        </div>
      )}

      {/* Passo atual */}
      <main ref={mainRef} className="relative flex-1 overflow-y-auto overscroll-contain px-5 pb-6 pt-5 sm:px-8" onTouchStart={onTouchStart} onTouchEnd={onTouchEnd}>
        <div style={{ zoom: prefs.scale }}>
          {mise ? (
            <MisePlace recipe={recipe} factor={factor} checked={prepChecked} onToggle={onTogglePrep} />
          ) : finished ? (
            <div className="mx-auto flex max-w-xl animate-step-in flex-col items-center py-10 text-center">
              <span className="logo-orb h-28 w-28 animate-float">
                <Icon name="check" className="h-14 w-14" stroke={2.4} />
              </span>
              <h2 className="mt-8 text-5xl font-extrabold tracking-tight">Bom apetite!</h2>
              <p className="mt-3 text-xl text-ink-soft dark:text-white/70">Você concluiu {recipe.title.toLowerCase()}.</p>
              <div className="mt-8 flex flex-wrap justify-center gap-3">
                <FavoriteButton id={recipe.id} variant="large" />
                {recipe.chef && <FollowButton id={recipe.chef.id} />}
              </div>
              {recipe.chef && <p className="mt-3 text-sm text-ink-mute">Receita de {recipe.chef.name}</p>}
            </div>
          ) : (
            <StepView
              key={index}
              step={step}
              index={index}
              factor={factor}
              direction={direction}
              timer={timers.timers[index]}
              onStart={startTimer}
              onPause={() => timers.pause(index)}
              onReset={() => timers.reset(index)}
              checked={checked}
              onToggle={toggleStepIngredient}
            />
          )}
        </div>
      </main>

      {/* Timers de outros passos */}
      {dock.length > 0 && (
        <div className="relative mx-auto flex w-full max-w-5xl flex-wrap gap-2 px-3 pb-2 sm:px-6">
          {dock.map((t) => (
            <button
              key={t.key}
              type="button"
              onClick={() => go(t.key)}
              className={`inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-bold ring-1 transition active:scale-95 ${
                t.done ? 'animate-ring bg-mint-100 text-ink ring-mint-300' : 'bg-white/80 ring-white dark:bg-white/10 dark:ring-white/10'
              }`}
            >
              {t.done ? <Icon name="check" className="h-4 w-4 text-mint-700" stroke={3} /> : <span className={`h-2 w-2 rounded-full ${t.running ? 'animate-pulse bg-iris-500' : 'bg-ink-mute'}`} />}
              Passo {t.key + 1} · {t.done ? 'pronto' : clock(t.remaining)}
            </button>
          ))}
        </div>
      )}

      {/* Rodapé: a seguir + voltar/avançar (zona do polegar) */}
      <footer className="relative border-t border-white/60 bg-white/40 px-3 pb-[max(.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur-xl dark:border-white/5 dark:bg-white/[.03] sm:px-6">
        <div className="mx-auto max-w-5xl">
          {!finished && nextStep && (
            <p className="mb-2.5 truncate px-1 text-sm text-ink-mute dark:text-white/50">
              <span className="font-bold uppercase tracking-wider">A seguir</span> · {nextStep.action}
            </p>
          )}
          <div className="flex gap-2.5">
            {finished ? (
              <>
                <button type="button" className="btn btn-glass h-16 flex-1 text-base dark:bg-white/10 dark:text-white dark:ring-white/10" onClick={() => go(MISE)}>
                  <Icon name="rotate" /> Recomeçar
                </button>
                <button type="button" className="btn btn-primary h-16 flex-[2] text-lg dark:bg-white dark:text-ink" onClick={requestClose}>
                  Voltar à receita
                </button>
              </>
            ) : (
              <>
                <button type="button" className="btn btn-glass h-16 w-16 shrink-0 p-0 dark:bg-white/10 dark:text-white dark:ring-white/10" onClick={prev} disabled={mise} aria-label="Passo anterior">
                  <Icon name="chevron-left" className="h-7 w-7" />
                </button>
                <button type="button" className="btn btn-primary h-16 flex-1 text-lg dark:bg-white dark:text-ink" onClick={next}>
                  {mise ? 'Começar o passo 1' : index === total - 1 ? 'Concluir receita' : 'Próximo passo'}
                  <Icon name={index === total - 1 ? 'check' : 'arrow-right'} className="h-6 w-6" stroke={2.2} />
                </button>
              </>
            )}
          </div>
        </div>
      </footer>

      {/* Folhas */}
      {sheet === 'steps' && (
        <Sheet title="Passos" onClose={() => setSheet(null)} wide>
          <button
            type="button"
            onClick={() => {
              go(MISE);
              setSheet(null);
            }}
            className={`mb-2 flex w-full items-center gap-4 rounded-3xl p-3 text-left ring-1 transition ${
              mise ? 'bg-iris-50 ring-iris-300 dark:bg-iris-500/15 dark:ring-iris-400/40' : 'bg-white/60 ring-white dark:bg-white/[.05] dark:ring-white/10'
            }`}
          >
            <span className={`inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${mise ? 'bg-iris-500 text-white' : 'bg-ink text-white dark:bg-white dark:text-ink'}`}>
              <Icon name="board" className="h-5 w-5" />
            </span>
            <span className="min-w-0 flex-1">
              <span className="block font-bold leading-snug">Mise en place</span>
              <span className="block text-sm text-ink-mute dark:text-white/55">Separar e preparar os ingredientes</span>
            </span>
          </button>
          <ol className="space-y-2">
            {steps.map((s, i) => (
              <li key={i}>
                <button
                  type="button"
                  onClick={() => {
                    go(i);
                    setSheet(null);
                  }}
                  className={`flex w-full items-center gap-4 rounded-3xl p-3 text-left ring-1 transition ${
                    i === index ? 'bg-iris-50 ring-iris-300 dark:bg-iris-500/15 dark:ring-iris-400/40' : 'bg-white/60 ring-white dark:bg-white/[.05] dark:ring-white/10'
                  }`}
                >
                  <span className={`inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-extrabold ${i < index ? 'bg-ink text-white dark:bg-white dark:text-ink' : i === index ? 'bg-iris-500 text-white' : 'bg-ink/5 dark:bg-white/10'}`}>
                    {i < index ? <Icon name="check" className="h-5 w-5" stroke={2.6} /> : i + 1}
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className="block font-bold leading-snug">{s.action}</span>
                    {settingsSummary(s) && <span className="block text-sm text-ink-mute dark:text-white/55">{settingsSummary(s)}</span>}
                  </span>
                  {timers.timers[i] && (
                    <span className="text-sm font-bold tabular-nums text-iris-600 dark:text-iris-300">{timers.timers[i].done ? 'pronto' : clock(timers.timers[i].remaining)}</span>
                  )}
                </button>
              </li>
            ))}
          </ol>
        </Sheet>
      )}

      {sheet === 'ingredients' && (
        <Sheet title={`Mise en place · ${servings} ${recipe.yieldLabel}`} onClose={() => setSheet(null)}>
          <ul className="space-y-2">
            {recipe.ingredients.map((ing, i) => {
              const done = prepChecked.has(i);
              const qty = scaleQty(ing.qty, factor, ing.unit);
              const header = ing.group && ing.group !== recipe.ingredients[i - 1]?.group;
              return (
                <li key={i}>
                  {header && <p className="mb-1 mt-4 text-xs font-bold uppercase tracking-[0.18em] text-ink-mute first:mt-0">{ing.group}</p>}
                  <button type="button" onClick={() => onTogglePrep(i)} aria-pressed={done} className="flex w-full items-center gap-3 rounded-2xl bg-white/60 px-4 py-3 text-left ring-1 ring-white dark:bg-white/[.05] dark:ring-white/10">
                    <span className={`inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full ring-2 ${done ? 'bg-mint-500 text-white ring-mint-500' : 'ring-ink/15 dark:ring-white/25'}`}>
                      {done && <Icon name="check" className="h-3.5 w-3.5" stroke={3} />}
                    </span>
                    <span className={done ? 'text-ink-mute line-through' : ''}>
                      <IngredientText qty={qty} unit={ing.unit} name={ing.name} />
                      {ing.note && <span className="font-semibold text-iris-600 dark:text-iris-300"> · {ing.note}</span>}
                    </span>
                  </button>
                </li>
              );
            })}
          </ul>
        </Sheet>
      )}

      {sheet === 'settings' && (
        <Sheet title="Ajustes" onClose={() => setSheet(null)}>
          <div className="space-y-5">
            <Segmented
              label="Tema"
              value={prefs.theme}
              onChange={(theme) => setPrefs({ theme })}
              options={[
                { value: 'light', label: 'Claro' },
                { value: 'dark', label: 'Escuro' },
              ]}
            />
            <Segmented
              label="Tamanho do texto"
              value={prefs.scale}
              onChange={(scale) => setPrefs({ scale })}
              options={[
                { value: 1, label: 'Normal' },
                { value: 1.15, label: 'Grande' },
                { value: 1.3, label: 'Enorme' },
              ]}
            />
            <div className="space-y-2.5">
              <Toggle label="Som dos timers" hint="Bipes quando um timer termina." checked={prefs.sound} onChange={(sound) => setPrefs({ sound })} />
              <Toggle
                label="Ler cada passo em voz alta"
                hint={ttsSupported ? 'O ChefMix lê o passo sempre que você avança.' : 'Seu navegador não tem leitura em voz alta.'}
                checked={prefs.speak}
                disabled={!ttsSupported}
                onChange={(speakOn) => setPrefs({ speak: speakOn })}
              />
              <Toggle
                label="Comandos de voz"
                hint={
                  voiceSupported
                    ? voice.status === 'denied'
                      ? 'Permita o microfone nas configurações do navegador.'
                      : 'Diga “próximo”, “voltar”, “repetir”, “iniciar timer” ou “ingredientes”. O microfone só fica ativo nesta tela.'
                    : 'Disponível no Chrome (Android/computador) e no Safari.'
                }
                checked={prefs.voice}
                disabled={!voiceSupported}
                onChange={(voiceOn) => setPrefs({ voice: voiceOn })}
              />
            </div>
            <p className="rounded-3xl bg-white/50 p-4 text-sm text-ink-soft dark:bg-white/[.05] dark:text-white/60">
              <strong className="text-ink dark:text-white">Tela sempre ligada: </strong>
              {wake === 'on' ? 'ativa enquanto o modo cozinhar estiver aberto.' : wake === 'unsupported' ? 'indisponível neste navegador (exige HTTPS). Desative o bloqueio automático da tela, se puder.' : 'aguardando permissão do navegador.'}
            </p>
            {voice.heard && prefs.voice && <p className="text-sm text-ink-mute">Última frase ouvida: “{voice.heard}”</p>}
            <button
              type="button"
              className="btn btn-glass w-full dark:bg-white/10 dark:text-white dark:ring-white/10"
              onClick={() => {
                setSheet(null);
                setOnboarding(true);
              }}
            >
              <Icon name="help" /> Ver o tutorial de novo
            </button>
          </div>
        </Sheet>
      )}

      {/* Tutorial fora da raiz temática: sempre claro. */}
      {onboarding &&
        createPortal(
          <Onboarding
            onDone={() => {
              save('cmx:onboarded:2', true);
              setOnboarding(false);
              rootRef.current?.focus({ preventScroll: true });
            }}
          />,
          document.body,
        )}
    </div>
  );
}
