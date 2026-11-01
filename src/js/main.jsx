import '@fontsource-variable/plus-jakarta-sans';
import '../css/main.css';

import { createRoot } from 'react-dom/client';
import App from './components/App';
import { mountIslands, unmountIslands } from './islands';
import { initSwup } from './swup';
import { initChrome, updateCurrentNav, closeMenus } from './chrome';

function boot() {
  initChrome();

  // Raiz global (login e avisos) — fica fora do #swup.
  const root = document.getElementById('cmx-root');
  if (root) createRoot(root).render(<App />);

  mountIslands(document);
  updateCurrentNav();

  initSwup({
    beforeReplace: unmountIslands,
    afterReplace: () => mountIslands(document.getElementById('swup')),
    onView: () => {
      updateCurrentNav();
      closeMenus();
    },
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
