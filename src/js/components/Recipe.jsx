import { useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { FLAMES, LIDS, SPEEDS, STIR, amount, clock, placeOf, scaleQty } from '../lib/format';
import { DAY, HOUR, load, remove, save } from '../lib/storage';
import Icon from './Icon';
import IngredientText from './IngredientText';
import CookMode, { MISE, progressKey } from './cook/CookMode';

function Params({ step }) {
  const items = [];
  if (step.flame) items.push(['flame', `fogo ${FLAMES[step.flame].toLowerCase()}`]);
  if (step.temp) items.push(['oven', `forno ${step.temp}°`]);
  if (step.speed) items.push(['speed', `velocidade ${SPEEDS[step.speed].toLowerCase()}`]);
  if (step.seconds) items.push(['timer', clock(step.seconds)]);
  if (step.stir) items.push(['stir', step.stir === 'nao' ? 'não mexa' : `mexa ${STIR[step.stir].toLowerCase()}`]);
  if (step.lid) items.push(['lid', LIDS[step.lid].toLowerCase()]);
  if (!items.length) return null;
  return (
    <span className="mt-2 flex flex-wrap gap-1.5">
      {items.map(([icon, label]) => (
        <span key={icon} className="inline-flex items-center gap-1 rounded-full bg-iris-50 px-2.5 py-1 text-xs font-bold text-iris-700 ring-1 ring-iris-100">
          <Icon name={icon} className="h-3.5 w-3.5" stroke={2} />
          {label}
        </span>
      ))}
    </span>
  );
}

/**
 * Página da receita (ilha React): pré-preparo e visão geral dos passos.
 *
 * Estratégia de orientação:
 *  1. Mise en place: ajustar porções (tudo recalcula) e marcar cada ingrediente medido e preparado.
 *  2. Visão geral: todos os passos com onde, fogo e tempo — tocar em um passo começa dali.
 *  3. Botão fixo no polegar: "Começar" ou "Continuar do passo N" (progresso salvo no navegador).
 *  4. Modo cozinhar em tela cheia (CookMode), que começa pela mise en place.
 */
export default function Recipe(recipe) {
  const { id, servings: base, yieldLabel, ingredients, steps, utensils } = recipe;

  const [progress, setProgress] = useState(() => load(progressKey(id), null, 7 * DAY));
  const [servings, setServings] = useState(() => progress?.servings || load(`cmx:servings:${id}`, base, 30 * DAY));
  const [checked, setChecked] = useState(() => new Set(load(`cmx:prep:${id}`, [], 12 * HOUR)));
  const [cook, setCook] = useState(null);

  const factor = servings / base;

  useEffect(() => save(`cmx:servings:${id}`, servings), [servings]);
  useEffect(() => save(`cmx:prep:${id}`, [...checked]), [checked]);

  const togglePrep = (i) =>
    setChecked((current) => {
      const next = new Set(current);
      if (next.has(i)) next.delete(i);
      else next.add(i);
      return next;
    });

  const groups = useMemo(() => {
    const list = [];
    ingredients.forEach((ing, i) => {
      const last = list[list.length - 1];
      if (last && last.name === ing.group) last.items.push([ing, i]);
      else list.push({ name: ing.group, items: [[ing, i]] });
    });
    return list;
  }, [ingredients]);

  const open = (start = 0) => setCook({ start });
  const close = () => {
    setCook(null);
    setProgress(load(progressKey(id), null, 7 * DAY));
  };
  const restart = () => {
    remove(progressKey(id));
    setProgress(null);
    open(MISE);
  };

  const resumeStep = progress && progress.step > 0 && progress.step < steps.length ? progress.step : 0;
  const allChecked = checked.size >= ingredients.length && ingredients.length > 0;

  const cta = resumeStep ? `Continuar do passo ${resumeStep + 1}` : 'Começar a cozinhar';

  if (!steps.length) {
    return <p className="glass-card mt-14 rounded-4xl p-6 text-ink-soft">Esta receita ainda não tem passos cadastrados.</p>;
  }

  return (
    <>
      <div className="mt-14 grid gap-10 lg:grid-cols-[24rem_1fr] lg:gap-12">
        {/* Antes de começar */}
        <aside className="lg:sticky lg:top-28 lg:self-start">
          <div className="glass rounded-5xl p-6 sm:p-7">
            <p className="eyebrow">Antes de começar</p>
            <h2 className="text-2xl font-extrabold tracking-tight">Mise en place</h2>
            <p className="mt-1 text-ink-soft">Separe e prepare tudo antes de ligar o fogo. Com cada ingrediente medido e picado, os passos fluem sem correria.</p>

            {/* Porções */}
            <div className="mt-6 flex items-center justify-between rounded-full bg-white/75 p-1.5 ring-1 ring-white">
              <button type="button" className="btn h-12 w-12 rounded-full bg-mist p-0 hover:bg-mist-deep" onClick={() => setServings((s) => Math.max(1, s - 1))} disabled={servings <= 1} aria-label="Menos porções">
                <Icon name="minus" stroke={2.4} />
              </button>
              <p className="text-center leading-tight" aria-live="polite">
                <span className="block text-2xl font-extrabold tabular-nums">{servings}</span>
                <span className="text-xs font-bold uppercase tracking-wider text-ink-mute">{yieldLabel}</span>
              </p>
              <button type="button" className="btn h-12 w-12 rounded-full bg-mist p-0 hover:bg-mist-deep" onClick={() => setServings((s) => Math.min(base * 6, s + 1))} aria-label="Mais porções">
                <Icon name="plus" stroke={2.4} />
              </button>
            </div>
            {servings !== base && (
              <p className="mt-2 text-center text-xs text-ink-mute">
                Quantidades recalculadas · original: {base} {yieldLabel}.{' '}
                <button type="button" className="font-semibold text-iris-600 underline" onClick={() => setServings(base)}>
                  Restaurar
                </button>
              </p>
            )}

            {/* Checklist de ingredientes */}
            <div className="mt-7 flex items-center justify-between">
              <h3 className="font-bold">Ingredientes</h3>
              <span className={`text-sm font-bold tabular-nums ${allChecked ? 'text-mint-700' : 'text-ink-mute'}`}>
                {allChecked ? 'Tudo pronto!' : `${checked.size} de ${ingredients.length} prontos`}
              </span>
            </div>
            <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-ink/5">
              <div className="h-full rounded-full bg-gradient-to-r from-iris-500 to-mint-400 transition-all duration-500" style={{ width: `${(checked.size / Math.max(1, ingredients.length)) * 100}%` }} />
            </div>

            <div className="mt-4 space-y-4">
              {groups.map((group, g) => (
                <div key={g}>
                  {group.name && <p className="mb-1.5 text-xs font-bold uppercase tracking-[0.18em] text-ink-mute">{group.name}</p>}
                  <ul className="space-y-1.5">
                    {group.items.map(([ing, i]) => {
                      const done = checked.has(i);
                      return (
                        <li key={i}>
                          <button
                            type="button"
                            onClick={() => togglePrep(i)}
                            aria-pressed={done}
                            className={`flex min-h-[3.25rem] w-full items-center gap-3 rounded-2xl px-3.5 py-2 text-left transition active:scale-[.98] ${done ? 'bg-mint-100/60' : 'bg-white/60 hover:bg-white'}`}
                          >
                            <span className={`inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full ring-2 transition ${done ? 'bg-mint-500 text-white ring-mint-500' : 'ring-ink/15'}`}>
                              {done && <Icon name="check" className="h-3.5 w-3.5" stroke={3} />}
                            </span>
                            <span className={`leading-snug ${done ? 'text-ink-mute line-through' : ''}`}>
                              <IngredientText qty={scaleQty(ing.qty, factor, ing.unit)} unit={ing.unit} name={ing.name} />
                              {ing.note && <span className="font-semibold text-iris-600"> · {ing.note}</span>}
                            </span>
                          </button>
                        </li>
                      );
                    })}
                  </ul>
                </div>
              ))}
            </div>

            {utensils.length > 0 && (
              <>
                <h3 className="mt-7 font-bold">Utensílios</h3>
                <div className="mt-2 flex flex-wrap gap-1.5">
                  {utensils.map((u) => (
                    <span key={u} className="inline-flex items-center gap-1.5 rounded-full bg-white/60 px-3 py-1.5 text-sm font-medium text-ink-soft ring-1 ring-white">
                      <Icon name="tool" className="h-3.5 w-3.5" /> {u}
                    </span>
                  ))}
                </div>
              </>
            )}

            {/* Começar (tablet/desktop; no celular há a barra fixa) */}
            <div className="mt-8 hidden md:block">
              <button type="button" className="btn btn-accent btn-lg w-full" onClick={() => open(resumeStep || MISE)}>
                <Icon name="play" className="h-5 w-5" fill="currentColor" stroke={1} /> {cta}
              </button>
              {resumeStep > 0 && (
                <button type="button" className="mt-2 w-full text-center text-sm font-semibold text-ink-mute hover:text-ink" onClick={restart}>
                  Recomeçar do início
                </button>
              )}
              <p className="mt-3 text-center text-xs text-ink-mute">Abre em tela cheia: primeiro a mise en place, depois um passo por vez.</p>
            </div>
          </div>
        </aside>

        {/* Visão geral dos passos */}
        <section aria-labelledby="steps-title">
          <div className="flex items-end justify-between gap-4">
            <h2 id="steps-title" className="section-title">
              Passo a passo
            </h2>
            <span className="pb-1 text-sm font-semibold text-ink-mute">{steps.length} passos</span>
          </div>
          <p className="mt-2 text-ink-soft">Toque em um passo para começar a partir dele.</p>

          <ol className="mt-6 space-y-3">
            {steps.map((step, i) => {
              const place = placeOf(step);
              const current = resumeStep > 0 && i === resumeStep;
              return (
                <li key={i}>
                  <button
                    type="button"
                    onClick={() => open(i)}
                    className={`glass-card group flex w-full items-start gap-4 rounded-4xl p-4 text-left transition hover:-translate-y-0.5 hover:shadow-float sm:p-5 ${current ? 'ring-2 ring-iris-400' : ''}`}
                  >
                    <span className={`inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-lg font-extrabold ${resumeStep > i ? 'bg-ink text-white' : 'bg-mist text-ink'}`}>
                      {resumeStep > i ? <Icon name="check" className="h-5 w-5" stroke={2.6} /> : i + 1}
                    </span>
                    <span className="min-w-0 flex-1">
                      <span className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-ink-mute">
                        <Icon name={place.icon} className="h-3.5 w-3.5" /> {place.label}
                        {current && <span className="ml-1 rounded-full bg-iris-500 px-2 py-0.5 text-[10px] text-white">você parou aqui</span>}
                      </span>
                      <span className="mt-1 block text-lg font-bold leading-snug">{step.action}</span>
                      {step.ingredients.length > 0 && (
                        <span className="mt-1 block text-sm text-ink-soft">
                          {step.ingredients
                            .map((ing) => (ing.unit === 'a gosto' ? `${ing.name} a gosto` : `${amount(scaleQty(ing.qty, factor, ing.unit), ing.unit)} ${ing.name}`.trim()))
                            .join(' · ')}
                        </span>
                      )}
                      <Params step={step} />
                      {step.done ? (
                        <span className="mt-2 block text-sm leading-relaxed text-ink-mute">
                          <strong className="font-semibold text-mint-700">Pronto quando</strong> {step.done}.
                        </span>
                      ) : (
                        step.text && <span className="mt-2 line-clamp-2 block text-sm leading-relaxed text-ink-mute">{step.text}</span>
                      )}
                    </span>
                    <span className="mt-1 hidden shrink-0 items-center gap-1 rounded-full bg-white/80 px-3 py-1.5 text-xs font-bold text-ink-soft opacity-0 ring-1 ring-white transition group-hover:opacity-100 sm:inline-flex">
                      Começar daqui <Icon name="arrow-right" className="h-3.5 w-3.5" />
                    </span>
                  </button>
                </li>
              );
            })}
          </ol>
        </section>
      </div>

      {/* Barra fixa no celular, acima das abas */}
      <div className="h-24 md:hidden" aria-hidden="true" />
      <div className="fixed inset-x-3 z-30 md:hidden" style={{ bottom: 'calc(max(0.75rem, env(safe-area-inset-bottom)) + 4.85rem)' }}>
        <div className="glass mx-auto flex max-w-md items-center gap-3 rounded-full p-1.5 pl-5">
          <span className="min-w-0 flex-1 text-sm leading-tight">
            <span className="block font-bold">{allChecked ? 'Tudo pronto!' : `Mise en place · ${checked.size}/${ingredients.length}`}</span>
            <span className="block truncate text-ink-mute">
              {servings} {yieldLabel} · {steps.length} passos
            </span>
          </span>
          <button type="button" className="btn btn-accent h-12 shrink-0 px-5" onClick={() => open(resumeStep || MISE)}>
            <Icon name="play" className="h-4 w-4" fill="currentColor" stroke={1} />
            {resumeStep ? `Passo ${resumeStep + 1}` : 'Começar'}
          </button>
        </div>
      </div>

      {cook &&
        createPortal(<CookMode recipe={recipe} servings={servings} startAt={cook.start} prepChecked={checked} onTogglePrep={togglePrep} onClose={close} />, document.body)}
    </>
  );
}
