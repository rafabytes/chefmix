import { FLAMES, LIDS, SPEEDS, STIR, clock, placeOf } from '../../lib/format';
import Icon from '../Icon';

/** Chamas acesas conforme a intensidade do fogo (leitura instantânea, sem precisar ler a palavra). */
function FlameMeter({ level }) {
  const lit = { baixo: 1, medio: 2, alto: 3 }[level] || 0;
  return (
    <span className="flex items-end gap-0.5" aria-hidden="true">
      {[1, 2, 3].map((n) => (
        <Icon key={n} name="flame" fill={n <= lit ? 'currentColor' : 'none'} className={`${n === 2 ? 'h-5 w-5' : 'h-4 w-4'} ${n <= lit ? 'text-peach-500' : 'text-ink/20 dark:text-white/20'}`} stroke={1.6} />
      ))}
    </span>
  );
}

/**
 * Os ajustes do passo em mostradores grandes: o que regular (fogo, forno, velocidade),
 * por quanto tempo e como conduzir (mexer, tampa).
 */
export default function Dials({ step }) {
  const items = [];
  if (step.flame) items.push({ key: 'flame', icon: 'flame', label: 'Fogo', value: FLAMES[step.flame], extra: <FlameMeter level={step.flame} /> });
  if (step.temp) items.push({ key: 'temp', icon: 'oven', label: 'Forno', value: `${step.temp}°` });
  if (step.speed) items.push({ key: 'speed', icon: 'speed', label: 'Velocidade', value: SPEEDS[step.speed] });
  if (step.seconds) items.push({ key: 'time', icon: 'timer', label: 'Tempo', value: clock(step.seconds) });
  if (step.stir) items.push({ key: 'stir', icon: 'stir', label: 'Mexer', value: STIR[step.stir], small: true });
  if (step.lid) items.push({ key: 'lid', icon: 'lid', label: 'Tampa', value: LIDS[step.lid], small: true });

  if (!items.length) return null;

  const cols = items.length === 1 ? 'grid-cols-1' : items.length === 3 ? 'grid-cols-3' : 'grid-cols-2';
  const three = items.length === 3;

  // Três mostradores lado a lado num celular de 360–390 px: valores menores para caber.
  const size = (item) => {
    if (item.small) return '!text-xl sm:!text-2xl';
    const chars = String(item.value).length;
    if (three) return chars > 4 ? '!text-[1.6rem] sm:!text-4xl' : '!text-[1.9rem] sm:!text-5xl';
    return chars > 5 ? '!text-[1.75rem] sm:!text-4xl' : '!text-[2.6rem] sm:!text-5xl';
  };

  const title = placeOf(step).adjust;

  return (
    <div>
      {title && <p className="mb-2.5 text-xs font-bold uppercase tracking-[0.18em] text-iris-600 dark:text-iris-300">{title}</p>}
      <div className={`grid gap-2.5 ${cols}`}>
        {items.map((item) => (
          <div key={item.key} className={`dial ${item.small ? 'min-h-[5.25rem]' : 'min-h-[7rem]'} text-center dark:bg-white/[.07] dark:ring-white/10 dark:[box-shadow:none]`}>
            <span className="dial-label dark:text-white/60">
              <Icon name={item.icon} className="h-4 w-4" />
              {item.label}
            </span>
            <span className={`dial-value ${size(item)} leading-none`}>{item.value}</span>
            {item.extra}
          </div>
        ))}
      </div>
    </div>
  );
}
