import { useState } from 'react';
import { api } from '../lib/api';
import { toast } from '../lib/events';
import Icon from './Icon';

/** Exclusão da própria conta (assinantes), em dois toques. */
export default function DeleteAccount() {
  const [confirming, setConfirming] = useState(false);
  const [busy, setBusy] = useState(false);

  const remove = async () => {
    setBusy(true);
    try {
      await api('me', { method: 'POST', body: { confirm: true } });
      window.location.href = window.CHEFMIX?.homeUrl || '/';
    } catch (error) {
      toast(error.message, 'error');
      setBusy(false);
    }
  };

  if (!confirming) {
    return (
      <button type="button" className="btn btn-glass text-rose-700" onClick={() => setConfirming(true)}>
        <Icon name="trash" className="h-4 w-4" /> Excluir conta
      </button>
    );
  }

  return (
    <div className="flex flex-wrap items-center gap-2 rounded-full bg-rose-100 p-1 pl-4 text-sm font-semibold text-rose-700" role="alert">
      Apagar conta e favoritos?
      <button type="button" className="btn h-9 bg-white px-4 text-ink" onClick={() => setConfirming(false)} disabled={busy}>
        Cancelar
      </button>
      <button type="button" className="btn h-9 bg-rose-500 px-4 text-white" onClick={remove} disabled={busy}>
        {busy ? 'Apagando…' : 'Excluir'}
      </button>
    </div>
  );
}
