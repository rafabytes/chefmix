import { useDialog } from '../../lib/useDialog';
import Icon from '../Icon';

/** Folha que sobe de baixo (celular) ou painel central (tablet/desktop). */
export default function Sheet({ title, onClose, children, footer, wide = false }) {
  const ref = useDialog(true, onClose);

  return (
    <div className="fixed inset-0 z-[130] flex items-end justify-center bg-ink/30 backdrop-blur-sm sm:items-center sm:p-6" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div
        ref={ref}
        role="dialog"
        aria-modal="true"
        aria-label={title}
        tabIndex={-1}
        className={`glass glass-solid flex max-h-[88vh] w-full animate-sheet-in flex-col rounded-t-5xl outline-none sm:rounded-5xl ${wide ? 'sm:max-w-2xl' : 'sm:max-w-lg'}`}
      >
        <div className="mx-auto mt-2.5 h-1.5 w-12 rounded-full bg-ink/15 dark:bg-white/20 sm:hidden" aria-hidden="true" />
        <div className="flex items-center justify-between gap-4 px-6 pb-2 pt-4 sm:pt-6">
          <h2 className="text-2xl font-extrabold tracking-tight">{title}</h2>
          <button type="button" className="icon-btn -mr-2 dark:text-white/70 dark:hover:bg-white/10" onClick={onClose} aria-label="Fechar">
            <Icon name="x" />
          </button>
        </div>
        <div className="flex-1 overflow-y-auto px-6 pb-6">{children}</div>
        {footer && <div className="border-t border-ink/5 px-6 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] dark:border-white/10">{footer}</div>}
      </div>
    </div>
  );
}
