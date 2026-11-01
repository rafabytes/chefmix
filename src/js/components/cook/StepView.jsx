import { placeOf, scaleQty } from '../../lib/format';
import Icon from '../Icon';
import IngredientText from '../IngredientText';
import Dials from './Dials';
import TimerControl from './TimerControl';

function Callout({ tone, icon, title, children }) {
  const tones = {
    rose: 'bg-rose-100 text-rose-700 ring-rose-400/30 dark:bg-rose-500/15 dark:text-rose-200',
    lemon: 'bg-lemon-100 text-lemon-700 ring-lemon-400/40 dark:bg-lemon-400/10 dark:text-lemon-300',
    mint: 'bg-mint-100 text-mint-700 ring-mint-300/60 dark:bg-mint-500/15 dark:text-mint-300',
  };
  return (
    <div className={`flex gap-3 rounded-3xl p-4 ring-1 ${tones[tone]}`} role={tone === 'rose' ? 'alert' : undefined}>
      <Icon name={icon} className="mt-0.5 h-6 w-6 shrink-0" stroke={2} />
      <p className="text-lg leading-snug">
        <strong className="font-bold">{title} </strong>
        {children}
      </p>
    </div>
  );
}

/**
 * Um passo em tela cheia.
 * Celular (uma coluna): onde + o quê → com o quê → como regular → como saber que está pronto → timer → detalhes.
 * Tablet/desktop (duas colunas): texto à esquerda, ingredientes/ajustes/timer à direita.
 */
export default function StepView({ step, index, factor, timer, onStart, onPause, onReset, checked, onToggle, direction }) {
  const place = placeOf(step);

  return (
    <div
      className="mx-auto flex w-full max-w-5xl animate-step-in flex-col gap-6 lg:grid lg:grid-cols-[1.1fr_1fr] lg:grid-rows-[auto_auto_auto_1fr] lg:gap-x-12 lg:[grid-template-areas:'head_ings'_'text_dials'_'text_done'_'text_timer']"
      style={{ '--from': direction < 0 ? '-36px' : '36px' }}
    >
      <header className="[grid-area:head]">
        <span className="inline-flex items-center gap-2 rounded-full bg-white/80 px-3.5 py-1.5 text-sm font-bold text-ink-soft ring-1 ring-white dark:bg-white/10 dark:text-white/80 dark:ring-white/10">
          <Icon name={place.icon} className="h-4 w-4" />
          {place.label}
        </span>
        <h2 className="mt-4 text-[clamp(2.1rem,8.5vw,4rem)] font-extrabold leading-[1.04] tracking-tight" id={`step-title-${index}`}>
          {step.action}
        </h2>
      </header>

      {step.ingredients.length > 0 && (
        <section className="[grid-area:ings]" aria-label="Ingredientes deste passo">
          <p className="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-ink-mute dark:text-white/50">{place.into}</p>
          <ul className="grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
            {step.ingredients.map((ing, i) => {
              const key = `${index}-${i}`;
              const done = checked.has(key);
              const qty = scaleQty(ing.qty, factor, ing.unit);
              return (
                <li key={key}>
                  <button
                    type="button"
                    onClick={() => onToggle(key)}
                    aria-pressed={done}
                    className={`flex min-h-[3.75rem] w-full items-center gap-3 rounded-2xl px-4 py-2.5 text-left ring-1 transition active:scale-[.98] ${
                      done ? 'bg-mint-100/70 ring-mint-300/60 dark:bg-mint-500/10 dark:ring-mint-500/30' : 'bg-white/85 ring-white dark:bg-white/[.07] dark:ring-white/10'
                    }`}
                  >
                    <span className={`inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full ring-2 transition ${done ? 'bg-mint-500 text-white ring-mint-500' : 'ring-ink/15 dark:ring-white/25'}`}>
                      {done && <Icon name="check" className="h-4 w-4" stroke={3} />}
                    </span>
                    <span className={`text-lg leading-tight ${done ? 'text-ink-mute line-through dark:text-white/40' : ''}`}>
                      <IngredientText qty={qty} unit={ing.unit} name={ing.name} strong="font-extrabold" />
                    </span>
                  </button>
                </li>
              );
            })}
          </ul>
        </section>
      )}

      <div className="[grid-area:dials] empty:hidden">
        <Dials step={step} />
      </div>

      {step.done && (
        <div className="[grid-area:done]">
          <Callout tone="mint" icon="eye" title="Pronto quando">
            {step.done}.
          </Callout>
        </div>
      )}

      <div className="[grid-area:timer] empty:hidden">
        <TimerControl step={step} timer={timer} onStart={onStart} onPause={onPause} onReset={onReset} />
      </div>

      <div className="space-y-4 [grid-area:text] empty:hidden">
        {step.text && <p className="text-xl leading-relaxed text-ink-soft dark:text-white/75 sm:text-2xl sm:leading-relaxed">{step.text}</p>}
        {step.warning && (
          <Callout tone="rose" icon="alert" title="Atenção:">
            {step.warning}
          </Callout>
        )}
        {step.tip && (
          <Callout tone="lemon" icon="bulb" title="Dica:">
            {step.tip}
          </Callout>
        )}
        {step.image?.url && <img src={step.image.url} alt={step.image.alt || ''} className="w-full rounded-4xl object-cover" loading="lazy" />}
      </div>
    </div>
  );
}
