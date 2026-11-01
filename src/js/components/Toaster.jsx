import { useEffect, useState } from 'react';
import { TOAST_EVENT } from '../lib/events';
import Icon from './Icon';

let nextId = 1;

const ICONS = { success: 'check', error: 'alert', info: 'sparkle' };

export default function Toaster() {
  const [items, setItems] = useState([]);

  useEffect(() => {
    const onToast = (event) => {
      const id = nextId++;
      const { message, type } = event.detail;
      setItems((list) => [...list.slice(-2), { id, message, type }]);
      window.setTimeout(() => setItems((list) => list.filter((t) => t.id !== id)), 4500);
    };
    window.addEventListener(TOAST_EVENT, onToast);
    return () => window.removeEventListener(TOAST_EVENT, onToast);
  }, []);

  return (
    <div className="pointer-events-none fixed inset-x-0 top-20 z-[120] flex flex-col items-center gap-2 px-4" aria-live="polite">
      {items.map((item) => (
        <div key={item.id} role="status" className="glass pointer-events-auto flex max-w-md animate-sheet-in items-center gap-3 rounded-full py-2.5 pl-2.5 pr-5 text-sm font-semibold">
          <span
            className={`inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white ${
              item.type === 'error' ? 'bg-rose-500' : item.type === 'success' ? 'bg-mint-500' : 'bg-iris-500'
            }`}
          >
            <Icon name={ICONS[item.type] || 'sparkle'} className="h-4 w-4" stroke={2.4} />
          </span>
          {item.message}
        </div>
      ))}
    </div>
  );
}
