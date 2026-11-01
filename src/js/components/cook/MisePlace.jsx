import { useMemo } from 'react';
import { scaleQty } from '../../lib/format';
import Icon from '../Icon';
import IngredientText from '../IngredientText';

/**
 * Mise en place: a etapa antes do passo 1. Separar utensílios e deixar cada ingrediente
 * medido e preparado (picado, em cubos, em temperatura ambiente) — assim, durante os passos,
 * a pessoa só junta, regula e acompanha.
 */
export default function MisePlace({ recipe, factor, checked, onToggle }) {
  const groups = useMemo(() => {
    const list = [];
    recipe.ingredients.forEach((ing, i) => {
      const last = list[list.length - 1];
      if (last && last.name === ing.group) last.items.push([ing, i]);
      else list.push({ name: ing.group, items: [[ing, i]] });
    });
    return list;
  }, [recipe.ingredients]);

  const total = recipe.ingredients.length;
  const done = recipe.ingredients.filter((_, i) => checked.has(i)).length;
  const all = done >= total && total > 0;

  return (
    <div className="mx-auto w-full max-w-5xl animate-step-in">
      <span className="inline-flex items-center gap-2 rounded-full bg-white/80 px-3.5 py-1.5 text-sm font-bold text-ink-soft ring-1 ring-white dark:bg-white/10 dark:text-white/80 dark:ring-white/10">
        <Icon name="board" className="h-4 w-4" /> Antes do passo 1
      </span>
      <h2 className="mt-4 text-[clamp(2.1rem,8.5vw,4rem)] font-extrabold leading-[1.04] tracking-tight">Mise en place</h2>
      <p className="mt-3 max-w-2xl text-xl leading-relaxed text-ink-soft dark:text-white/75">
        Separe e prepare tudo antes de ligar o fogo. Toque em cada item quando ele estiver medido e pronto para usar.
      </p>

      <div className="mt-7 grid gap-8 lg:grid-cols-[1.4fr_1fr] lg:gap-12">
        <section aria-label="Ingredientes">
          <div className="flex items-center justify-between">
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-ink-mute dark:text-white/50">Ingredientes</p>
            <p className={`text-sm font-bold tabular-nums ${all ? 'text-mint-700 dark:text-mint-300' : 'text-ink-mute dark:text-white/55'}`}>
              {all ? 'Tudo pronto!' : `${done} de ${total} prontos`}
            </p>
          </div>
          <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-ink/5 dark:bg-white/10">
            <div className="h-full rounded-full bg-gradient-to-r from-iris-500 to-mint-400 transition-all duration-500" style={{ width: `${(done / Math.max(1, total)) * 100}%` }} />
          </div>

          <div className="mt-4 space-y-5">
            {groups.map((group, g) => (
              <div key={g}>
                {group.name && <p className="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-ink-mute dark:text-white/50">{group.name}</p>}
                <ul className="grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                  {group.items.map(([ing, i]) => {
                    const isDone = checked.has(i);
                    return (
                      <li key={i}>
                        <button
                          type="button"
                          onClick={() => onToggle(i)}
                          aria-pressed={isDone}
                          className={`flex min-h-[3.75rem] w-full items-center gap-3 rounded-2xl px-4 py-2.5 text-left ring-1 transition active:scale-[.98] ${
                            isDone ? 'bg-mint-100/70 ring-mint-300/60 dark:bg-mint-500/10 dark:ring-mint-500/30' : 'bg-white/85 ring-white dark:bg-white/[.07] dark:ring-white/10'
                          }`}
                        >
                          <span className={`inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full ring-2 transition ${isDone ? 'bg-mint-500 text-white ring-mint-500' : 'ring-ink/15 dark:ring-white/25'}`}>
                            {isDone && <Icon name="check" className="h-4 w-4" stroke={3} />}
                          </span>
                          <span className={`leading-tight ${isDone ? 'text-ink-mute line-through dark:text-white/40' : ''}`}>
                            <span className="text-lg">
                              <IngredientText qty={scaleQty(ing.qty, factor, ing.unit)} unit={ing.unit} name={ing.name} strong="font-extrabold" />
                            </span>
                            {ing.note && <span className="mt-0.5 block text-sm font-bold text-iris-600 dark:text-iris-300">{ing.note}</span>}
                          </span>
                        </button>
                      </li>
                    );
                  })}
                </ul>
              </div>
            ))}
          </div>
        </section>

        {recipe.utensils.length > 0 && (
          <section aria-label="Utensílios">
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-ink-mute dark:text-white/50">Deixe à mão</p>
            <ul className="mt-3 space-y-2">
              {recipe.utensils.map((u) => (
                <li key={u} className="flex items-center gap-3 rounded-2xl bg-white/60 px-4 py-3 font-semibold ring-1 ring-white dark:bg-white/[.05] dark:ring-white/10">
                  <Icon name="tool" className="h-4 w-4 shrink-0 text-ink-mute" /> {u}
                </li>
              ))}
            </ul>
          </section>
        )}
      </div>
    </div>
  );
}
