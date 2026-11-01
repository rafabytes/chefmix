import { useState } from 'react';
import { useDialog } from '../../lib/useDialog';
import Icon from '../Icon';
import { voiceSupported } from './device';

/**
 * Tutorial curto na primeira vez que a pessoa abre o modo cozinhar.
 * Ensina o sistema em 4–5 cartões — cada um com um gesto/elemento que ela vai ver na tela.
 */
function slides() {
  const list = [
    {
      icon: 'board',
      tone: 'from-lemon-100 to-peach-100',
      title: 'Primeiro, a mise en place',
      text: 'A primeira tela lista tudo o que você vai usar. Meça, pique e deixe cada ingrediente à mão antes de ligar o fogo — e toque nele para marcar como pronto.',
      demo: (
        <div className="space-y-1.5 text-left text-sm">
          {[
            ['1 cebola', 'picada fina', true],
            ['2 dentes de alho', 'picados', false],
          ].map(([name, prep, done]) => (
            <div key={name} className="flex items-center gap-2.5 rounded-2xl bg-white/85 px-3 py-2 ring-1 ring-white">
              <span className={`inline-flex h-5 w-5 items-center justify-center rounded-full ring-2 ${done ? 'bg-mint-500 text-white ring-mint-500' : 'ring-ink/15'}`}>
                {done && <Icon name="check" className="h-3 w-3" stroke={3} />}
              </span>
              <span className={done ? 'text-ink-mute line-through' : ''}>
                <b>{name}</b> <span className="font-semibold text-iris-600">{prep}</span>
              </span>
            </div>
          ))}
        </div>
      ),
    },
    {
      icon: 'tap',
      tone: 'from-iris-200 to-sky-100',
      title: 'Um passo de cada vez',
      text: 'Toque em “Próximo passo” ou deslize a tela para o lado. A barra no topo mostra onde você está — toque nela para ver e pular para qualquer passo.',
      demo: (
        <div className="flex gap-1.5">
          {[0, 1, 2, 3, 4, 5].map((i) => (
            <span key={i} className={`h-2 flex-1 rounded-full ${i < 2 ? 'bg-ink' : i === 2 ? 'bg-gradient-to-r from-iris-500 to-mint-400' : 'bg-ink/10'}`} />
          ))}
        </div>
      ),
    },
    {
      icon: 'flame',
      tone: 'from-peach-100 to-lemon-100',
      title: 'Onde, quanto fogo e quando parar',
      text: 'Cada passo diz onde fazer (panela, frigideira, forno, liquidificador…), a intensidade do fogo, o tempo e se é para mexer. E mostra como saber que está pronto.',
      demo: (
        <div className="space-y-2">
          <div className="grid grid-cols-3 gap-2 text-center">
            {[
              ['Fogo', 'Médio'],
              ['Tempo', '3:00'],
              ['Mexer', 'Às vezes'],
            ].map(([label, value]) => (
              <div key={label} className="rounded-2xl bg-white/80 py-1.5 ring-1 ring-white">
                <p className="text-[10px] font-bold uppercase tracking-wider text-ink-mute">{label}</p>
                <p className="text-base font-extrabold">{value}</p>
              </div>
            ))}
          </div>
          <p className="rounded-2xl bg-mint-100 px-3 py-1.5 text-xs font-semibold text-mint-700">Pronto quando a cebola estiver transparente.</p>
        </div>
      ),
    },
    {
      icon: 'timer',
      tone: 'from-mint-100 to-sky-100',
      title: 'Timers que avisam',
      text: 'Toque em “Iniciar timer”: ele avisa com som e vibração, mesmo se você avançar para o próximo passo. E a tela não apaga enquanto você cozinha.',
      demo: (
        <div className="flex items-center justify-center gap-2 text-sm font-semibold">
          <span className="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1.5 ring-1 ring-white">
            <span className="h-2 w-2 rounded-full bg-iris-500" /> Passo 2 · 4:12
          </span>
          <span className="inline-flex items-center gap-1.5 rounded-full bg-mint-100 px-3 py-1.5 ring-1 ring-mint-300">
            <Icon name="check" className="h-3.5 w-3.5" stroke={3} /> Passo 1 pronto
          </span>
        </div>
      ),
    },
  ];
  if (voiceSupported) {
    list.push({
      icon: 'mic',
      tone: 'from-rose-100 to-iris-100',
      title: 'Mãos ocupadas?',
      text: 'Em Ajustes, ative os comandos de voz e diga “próximo”, “voltar”, “repetir” ou “iniciar timer”. O ChefMix também pode ler cada passo em voz alta.',
      demo: (
        <div className="flex flex-wrap justify-center gap-2 text-sm font-semibold">
          {['“próximo”', '“voltar”', '“repetir”', '“timer”'].map((word) => (
            <span key={word} className="rounded-full bg-white/80 px-3 py-1.5 ring-1 ring-white">
              {word}
            </span>
          ))}
        </div>
      ),
    });
  }
  return list;
}

export default function Onboarding({ onDone }) {
  const list = slides();
  const [i, setI] = useState(0);
  const ref = useDialog(true, onDone);
  const slide = list[i];
  const last = i === list.length - 1;

  return (
    <div className="fixed inset-0 z-[140] flex items-end justify-center bg-ink/40 p-0 text-ink backdrop-blur-md sm:items-center sm:p-6">
      <div ref={ref} role="dialog" aria-modal="true" aria-labelledby="onb-title" tabIndex={-1} className="glass glass-solid w-full max-w-md animate-sheet-in rounded-t-5xl p-7 pb-[max(1.75rem,env(safe-area-inset-bottom))] outline-none sm:rounded-5xl">
        <div className="flex items-center justify-between">
          <p className="text-xs font-bold uppercase tracking-[0.2em] text-iris-600">
            Como usar · {i + 1}/{list.length}
          </p>
          <button type="button" className="text-sm font-semibold text-ink-mute hover:text-ink" onClick={onDone}>
            Pular
          </button>
        </div>

        <div key={i} className="animate-step-in">
          <div className={`mt-6 flex min-h-[10rem] flex-col items-center justify-center gap-5 rounded-4xl bg-gradient-to-br p-5 ${slide.tone}`}>
            <span className="logo-orb h-14 w-14">
              <Icon name={slide.icon} className="h-7 w-7" />
            </span>
            <div className="w-full">{slide.demo}</div>
          </div>
          <h2 id="onb-title" className="mt-6 text-2xl font-extrabold tracking-tight">
            {slide.title}
          </h2>
          <p className="mt-2 text-lg leading-relaxed text-ink-soft">{slide.text}</p>
        </div>

        <div className="mt-7 flex items-center gap-3">
          <div className="flex flex-1 gap-1.5" aria-hidden="true">
            {list.map((_, n) => (
              <span key={n} className={`h-1.5 rounded-full transition-all ${n === i ? 'w-6 bg-ink' : 'w-1.5 bg-ink/20'}`} />
            ))}
          </div>
          {i > 0 && (
            <button type="button" className="btn btn-glass h-14 w-14 p-0" onClick={() => setI(i - 1)} aria-label="Anterior">
              <Icon name="chevron-left" className="h-6 w-6" />
            </button>
          )}
          <button type="button" className="btn btn-primary h-14 px-6 text-base" onClick={() => (last ? onDone() : setI(i + 1))}>
            {last ? 'Vamos cozinhar' : 'Próximo'}
            <Icon name="arrow-right" className="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>
  );
}
