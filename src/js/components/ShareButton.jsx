import { toast } from '../lib/events';
import Icon from './Icon';

/** Compartilhar: folha nativa do celular ou copia o link. */
export default function ShareButton({ title, url }) {
  const share = async () => {
    if (navigator.share) {
      try {
        await navigator.share({ title, url });
      } catch (error) {
        /* cancelado */
      }
      return;
    }
    try {
      await navigator.clipboard.writeText(url);
      toast('Link copiado', 'success');
    } catch (error) {
      window.prompt('Copie o link da receita:', url);
    }
  };

  return (
    <button type="button" onClick={share} className="btn btn-glass h-11 w-11 p-0" aria-label="Compartilhar receita">
      <Icon name="share" />
    </button>
  );
}
