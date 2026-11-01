import { lockScroll, unlockScroll } from './lib/scrollLock';

/**
 * Comportamentos do cabeçalho, da busca e da barra de abas (HTML em PHP, fora do contêiner do Swup).
 * Tudo usa delegação de eventos para continuar funcionando após cada navegação.
 */
function searchSheet() {
  return document.querySelector('[data-search-sheet]');
}

export function openSearch() {
  const sheet = searchSheet();
  if (!sheet || !sheet.hidden) return;
  sheet.hidden = false;
  lockScroll();
  window.requestAnimationFrame(() => sheet.querySelector('input[type="search"]')?.focus());
}

export function closeSearch() {
  const sheet = searchSheet();
  if (!sheet || sheet.hidden) return;
  sheet.hidden = true;
  unlockScroll();
}

export function initChrome() {
  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-search-open]')) {
      event.preventDefault();
      openSearch();
      return;
    }
    if (event.target.closest('[data-search-close]')) {
      closeSearch();
      return;
    }

    // Fecha o menu do usuário ao clicar fora.
    document.querySelectorAll('[data-user-menu][open]').forEach((menu) => {
      if (!menu.contains(event.target)) menu.removeAttribute('open');
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeSearch();
      closeMenus();
    }
    // "/" abre a busca (fora de campos de texto).
    if (event.key === '/' && !event.target.closest('input, textarea, [contenteditable]') && !document.documentElement.classList.contains('cmx-cooking')) {
      event.preventDefault();
      openSearch();
    }
  });

  // A busca é um GET comum (o Swup não intercepta formulários); fecha a folha ao enviar.
  document.addEventListener('submit', (event) => {
    if (event.target.closest('[data-search-sheet]')) closeSearch();
  });
}

/** Marca o link de navegação da página atual (cabeçalho e abas). */
export function updateCurrentNav() {
  const here = window.location.pathname.replace(/\/$/, '');
  document.querySelectorAll('[data-site-header] nav a[href], [data-tabbar] a[href]').forEach((link) => {
    const url = new URL(link.href, window.location.origin);
    const path = url.pathname.replace(/\/$/, '');
    const isHome = path === new URL(window.CHEFMIX?.homeUrl || '/', window.location.origin).pathname.replace(/\/$/, '');
    const isCurrent = !url.hash && (path === here || (!isHome && here.startsWith(path + '/')));
    if (isCurrent) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
  });
}

/** Fecha menus abertos ao navegar. */
export function closeMenus() {
  document.querySelectorAll('[data-user-menu][open]').forEach((menu) => menu.removeAttribute('open'));
  closeSearch();
}
