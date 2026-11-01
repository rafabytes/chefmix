/**
 * Formatação de quantidades, tempos e ajustes (fogo, forno, velocidade, mexer, tampa).
 * Mesmas regras do PHP (inc/recipes.php) + escala das porções.
 */

const FRACTIONS = [
  [0.25, '¼'],
  [0.33, '⅓'],
  [0.5, '½'],
  [0.67, '⅔'],
  [0.75, '¾'],
];

const PLURALS = {
  xícara: 'xícaras',
  'colher sopa': 'colheres (sopa)',
  'colher chá': 'colheres (chá)',
  unidade: 'unidades',
  dente: 'dentes',
  fatia: 'fatias',
  folha: 'folhas',
  ramo: 'ramos',
  maço: 'maços',
  lata: 'latas',
  pitada: 'pitadas',
};

const SINGULAR = { 'colher sopa': 'colher (sopa)', 'colher chá': 'colher (chá)' };

const METRIC = ['g', 'ml'];
const METRIC_LARGE = ['kg', 'l'];
const SPOONS = ['xícara', 'colher sopa', 'colher chá'];

export function formatNumber(value) {
  const int = Math.floor(value);
  const frac = Math.round((value - int) * 100) / 100;
  for (const [dec, glyph] of FRACTIONS) {
    if (Math.abs(frac - dec) < 0.04) return (int > 0 ? `${int} ` : '') + glyph;
  }
  return String(Math.round(value * 10) / 10).replace('.', ',');
}

/** Arredonda a quantidade escalada para algo que dá para medir na cozinha. */
export function scaleQty(qty, factor, unit) {
  if (qty === null || qty === undefined || qty === '') return null;
  const value = Number(qty) * factor;
  if (factor === 1) return Number(qty);
  if (METRIC.includes(unit)) {
    if (value >= 100) return Math.round(value / 5) * 5;
    if (value >= 10) return Math.round(value);
    return Math.max(1, Math.round(value * 2) / 2);
  }
  if (METRIC_LARGE.includes(unit)) return Math.round(value * 4) / 4 || 0.25;
  if (SPOONS.includes(unit)) return Math.max(0.25, Math.round(value * 4) / 4);
  return Math.max(0.5, Math.round(value * 2) / 2);
}

export function unitLabel(unit, qty) {
  if (!unit || unit === 'unidade') return '';
  if (qty > 1 && PLURALS[unit]) return PLURALS[unit];
  return SINGULAR[unit] || unit;
}

/** "200 g", "1 ½ xícara", "2 dentes", "a gosto". */
export function amount(qty, unit) {
  if (unit === 'a gosto') return 'a gosto';
  if (qty === null || qty === undefined) return '';
  return `${formatNumber(qty)} ${unitLabel(unit, qty)}`.trim();
}

export function clock(seconds) {
  const s = Math.max(0, Math.ceil(seconds));
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const sec = String(s % 60).padStart(2, '0');
  return h ? `${h}:${String(m).padStart(2, '0')}:${sec}` : `${m}:${sec}`;
}

export function duration(seconds) {
  if (!seconds) return '';
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  if (m && s) return `${m} min ${s} s`;
  return m ? `${m} min` : `${s} s`;
}

export function minutes(total) {
  const min = Math.round(total || 0);
  if (min <= 0) return '';
  if (min < 60) return `${min} min`;
  const h = Math.floor(min / 60);
  const m = min % 60;
  return m ? `${h} h ${m} min` : `${h} h`;
}

/* --------------------------------------------------- Onde e como (utensílios comuns) */

/**
 * Onde o passo acontece. `into` = título da lista de ingredientes do passo;
 * `adjust` = título dos ajustes (fogo, forno, velocidade). "recipiente" é o termo genérico.
 */
export const PLACES = {
  bancada: { label: 'Na bancada', icon: 'board', into: 'Separe' },
  tigela: { label: 'Na tigela', icon: 'bowl', into: 'Coloque na tigela' },
  panela: { label: 'Na panela', icon: 'pot', into: 'Coloque na panela', adjust: 'Regule o fogo' },
  frigideira: { label: 'Na frigideira', icon: 'pan', into: 'Coloque na frigideira', adjust: 'Regule o fogo' },
  liquidificador: { label: 'No liquidificador', icon: 'blender', into: 'Coloque no liquidificador', adjust: 'Regule o liquidificador' },
  processador: { label: 'No processador', icon: 'blender', into: 'Coloque no processador', adjust: 'Regule o processador' },
  batedeira: { label: 'Na batedeira', icon: 'mixer', into: 'Coloque na tigela da batedeira', adjust: 'Regule a batedeira' },
  forno: { label: 'No forno', icon: 'oven', into: 'Use', adjust: 'Regule o forno' },
  geladeira: { label: 'Na geladeira', icon: 'fridge', into: 'Use' },
  descanso: { label: 'Descanso', icon: 'hourglass', into: 'Use' },
  servir: { label: 'Para servir', icon: 'sparkle', into: 'Finalize com' },
};

export const placeOf = (step) => PLACES[step.place] || { label: 'No recipiente', icon: 'bowl', into: 'Coloque no recipiente' };

export const FLAMES = { baixo: 'Baixo', medio: 'Médio', alto: 'Alto' };
export const SPEEDS = { pulsar: 'Pulsar', baixa: 'Baixa', media: 'Média', alta: 'Alta' };
export const STIR = { sempre: 'Sem parar', vezes: 'Às vezes', nao: 'Não mexa' };
export const LIDS = { com: 'Tampada', entreaberta: 'Entreaberta', sem: 'Sem tampa' };

/** Resumo curto dos ajustes: "3:00 · fogo médio · mexa sem parar". */
export function settingsSummary(step) {
  return [
    step.seconds && clock(step.seconds),
    step.flame && `fogo ${FLAMES[step.flame]?.toLowerCase()}`,
    step.temp && `forno ${step.temp}°`,
    step.speed && `velocidade ${SPEEDS[step.speed]?.toLowerCase()}`,
    step.stir && (step.stir === 'nao' ? 'não mexa' : `mexa ${STIR[step.stir]?.toLowerCase()}`),
    step.lid && LIDS[step.lid]?.toLowerCase(),
  ]
    .filter(Boolean)
    .join(' · ');
}

/* -------------------------------------------------------------- Fala */

const SPOKEN_UNITS = [
  [/(\d) g\b/g, '$1 gramas'],
  [/(\d) kg\b/g, '$1 quilos'],
  [/(\d) ml\b/g, '$1 mililitros'],
  [/(\d) l\b/g, '$1 litros'],
  [/½/g, 'meio'],
  [/¼/g, 'um quarto'],
  [/¾/g, 'três quartos'],
  [/⅓/g, 'um terço'],
  [/⅔/g, 'dois terços'],
];

function spoken(text) {
  return SPOKEN_UNITS.reduce((acc, [re, to]) => acc.replace(re, to), text);
}

/** Texto lido em voz alta para um passo. */
export function stepSpeech(step, factor, number) {
  const parts = [`Passo ${number}. ${placeOf(step).label}. ${step.action}.`];
  if (step.ingredients.length) {
    const list = step.ingredients.map((ing) => `${amount(scaleQty(ing.qty, factor, ing.unit), ing.unit)} de ${ing.name}`.replace(/^ de /, ''));
    parts.push(`Use: ${list.join(', ')}.`);
  }
  const params = [];
  if (step.flame) params.push(`fogo ${FLAMES[step.flame].toLowerCase()}`);
  if (step.temp) params.push(`forno a ${step.temp} graus`);
  if (step.speed) params.push(`velocidade ${SPEEDS[step.speed].toLowerCase()}`);
  if (step.seconds) params.push(`por ${duration(step.seconds).replace('min', 'minutos').replace(/(\d) s$/, '$1 segundos')}`);
  if (step.stir) params.push(step.stir === 'nao' ? 'sem mexer' : `mexendo ${step.stir === 'sempre' ? 'sem parar' : 'de vez em quando'}`);
  if (step.lid) params.push(LIDS[step.lid].toLowerCase());
  if (params.length) parts.push(`${params.join(', ')}.`);
  if (step.text) parts.push(step.text);
  if (step.done) parts.push(`Está pronto quando ${step.done}.`);
  if (step.warning) parts.push(`Atenção: ${step.warning}`);
  return spoken(parts.join(' '));
}
