import { useState } from 'react';
import { api } from '../lib/api';
import { dataChanged, openLogin, toast } from '../lib/events';
import { getUser, setUserList, useUser } from '../lib/store';
import Icon from './Icon';

/**
 * Coração de favoritar. Variantes: `glass` (sobre a capa do card), `pill` (com contador) e `large`.
 * O estado vem do store do usuário — não do HTML — para não ficar velho no cache do Swup.
 */
export default function FavoriteButton({ id, count = 0, variant = 'glass' }) {
  const active = useUser((user) => Boolean(user && user.favorites.includes(Number(id))));
  const [total, setTotal] = useState(Number(count) || 0);
  const [busy, setBusy] = useState(false);

  const toggle = async (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (!getUser()) {
      openLogin('Entre para salvar esta receita nos seus favoritos.');
      return;
    }
    if (busy) return;

    const next = !active;
    const before = getUser().favorites;
    setBusy(true);
    setUserList('favorites', next ? [...before, Number(id)] : before.filter((f) => f !== Number(id)));
    setTotal((t) => Math.max(0, t + (next ? 1 : -1)));
    if (next && navigator.vibrate) navigator.vibrate(12);

    try {
      const data = await api(`favorites/${id}`, { method: 'POST', body: { on: next } });
      setUserList('favorites', data.list);
      setTotal(data.count);
      dataChanged();
      if (next) toast('Salva nos seus favoritos', 'success');
    } catch (error) {
      setUserList('favorites', before);
      setTotal((t) => Math.max(0, t + (next ? -1 : 1)));
      toast(error.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  const label = active ? 'Remover dos favoritos' : 'Salvar nos favoritos';
  const heart = <Icon name="heart" className={variant === 'large' ? 'h-6 w-6' : 'h-5 w-5'} fill={active ? 'currentColor' : 'none'} stroke={2} />;

  if (variant === 'pill' || variant === 'large') {
    return (
      <button type="button" onClick={toggle} aria-pressed={active} aria-label={label} className={`btn btn-glass ${variant === 'large' ? 'btn-lg' : 'h-11'} ${active ? 'text-rose-500' : ''}`}>
        {heart}
        <span className="text-ink">{active ? 'Salva' : 'Salvar'}</span>
        {variant === 'pill' && total > 0 && <span className="text-sm font-semibold text-ink-mute tabular-nums">{total}</span>}
      </button>
    );
  }

  return (
    <button
      type="button"
      onClick={toggle}
      aria-pressed={active}
      aria-label={label}
      className={`inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/70 ring-1 ring-white backdrop-blur-xl transition active:scale-90 ${
        active ? 'text-rose-500' : 'text-ink-soft hover:text-ink'
      }`}
    >
      {heart}
    </button>
  );
}
