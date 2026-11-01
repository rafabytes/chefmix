import { useState } from 'react';
import { api } from '../lib/api';
import { dataChanged, openLogin, toast } from '../lib/events';
import { getUser, setUserList, useUser } from '../lib/store';
import Icon from './Icon';

/** Seguir/deixar de seguir um chef. Atualiza também os contadores [data-follower-count] da página. */
export default function FollowButton({ id, size = 'md' }) {
  const chefId = Number(id);
  const isSelf = useUser((user) => Boolean(user && user.id === chefId));
  const active = useUser((user) => Boolean(user && user.following.includes(chefId)));
  const [busy, setBusy] = useState(false);

  if (isSelf) return null;

  const updateCounters = (count) => {
    document.querySelectorAll(`[data-follower-count="${chefId}"]`).forEach((el) => {
      el.textContent = String(count);
    });
  };

  const toggle = async (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (!getUser()) {
      openLogin('Entre para seguir este chef e encontrar as receitas dele em Salvos.');
      return;
    }
    if (busy) return;

    const next = !active;
    const before = getUser().following;
    setBusy(true);
    setUserList('following', next ? [...before, chefId] : before.filter((f) => f !== chefId));

    try {
      const data = await api(`follow/${chefId}`, { method: 'POST', body: { on: next } });
      setUserList('following', data.list);
      updateCounters(data.count);
      dataChanged();
    } catch (error) {
      setUserList('following', before);
      toast(error.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  const sizing = size === 'sm' ? 'h-10 px-4 text-sm' : 'h-12 px-6';

  return (
    <button type="button" onClick={toggle} aria-pressed={active} className={`btn ${sizing} ${active ? 'btn-glass' : 'btn-primary'}`}>
      {active ? (
        <>
          <Icon name="check" className="h-4 w-4" stroke={2.4} /> Seguindo
        </>
      ) : (
        <>
          <Icon name="plus" className="h-4 w-4" stroke={2.4} /> Seguir
        </>
      )}
    </button>
  );
}
