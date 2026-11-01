import { useEffect, useRef } from 'react';
import { lockScroll, unlockScroll } from './scrollLock';

const FOCUSABLE =
  'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

// Pilha de diálogos abertos: só o do topo responde a Esc/Tab (ex.: ajustes abertos sobre o modo cozinhar).
const stack = [];

/**
 * Comportamento de diálogo acessível: Esc fecha, Tab fica preso no diálogo,
 * a rolagem da página é travada e o foco volta para quem abriu.
 */
export function useDialog(open, onClose, { autoFocus = true } = {}) {
  const ref = useRef(null);
  const closeRef = useRef(onClose);
  closeRef.current = onClose;

  useEffect(() => {
    if (!open) return undefined;

    const id = Symbol('dialog');
    stack.push(id);

    const opener = document.activeElement;
    lockScroll();

    const node = ref.current;
    if (autoFocus) {
      const first = node && node.querySelector(FOCUSABLE);
      (first || node)?.focus?.({ preventScroll: true });
    } else {
      node?.focus?.({ preventScroll: true });
    }

    const onKey = (event) => {
      if (stack[stack.length - 1] !== id) return;

      if (event.key === 'Escape') {
        event.stopPropagation();
        closeRef.current();
        return;
      }
      if (event.key !== 'Tab' || !node) return;

      const items = [...node.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);
      if (!items.length) return;
      const firstEl = items[0];
      const lastEl = items[items.length - 1];
      if (event.shiftKey && document.activeElement === firstEl) {
        event.preventDefault();
        lastEl.focus();
      } else if (!event.shiftKey && document.activeElement === lastEl) {
        event.preventDefault();
        firstEl.focus();
      }
    };

    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('keydown', onKey);
      stack.splice(stack.indexOf(id), 1);
      unlockScroll();
      if (opener && typeof opener.focus === 'function') opener.focus({ preventScroll: true });
    };
  }, [open]);

  return ref;
}

/** true se este diálogo é o do topo da pilha (para atalhos de teclado do modo cozinhar). */
export const dialogOpen = () => stack.length > 0;
