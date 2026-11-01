import Swup from 'swup';
import SwupHeadPlugin from '@swup/head-plugin';
import SwupBodyClassPlugin from '@swup/body-class-plugin';
import SwupPreloadPlugin from '@swup/preload-plugin';
import SwupA11yPlugin from '@swup/a11y-plugin';
import SwupScrollPlugin from '@swup/scroll-plugin';
import { DATA_EVENT } from './lib/events';

const config = () => window.CHEFMIX || {};

/** Links que precisam de carregamento completo (admin, login, logout, arquivos). */
function shouldIgnore(url, el) {
  if (el && el.closest('[data-no-swup]')) return true;

  const target = new URL(url, window.location.origin);
  if (/\/wp-(admin|login|json)|\/wp-content\//.test(target.pathname)) return true;
  if (/\.(pdf|zip|png|jpe?g|webp|svg)$/i.test(target.pathname)) return true;

  return (config().noSwup || []).some((ignored) => {
    try {
      const ignoredUrl = new URL(ignored, window.location.origin);
      return target.origin === ignoredUrl.origin && target.pathname.startsWith(ignoredUrl.pathname) && ignoredUrl.pathname !== '/';
    } catch (error) {
      return false;
    }
  });
}

/**
 * Navegação sem recarregar (Swup).
 * `beforeReplace` roda antes da troca do conteúdo (desmontar React); `afterReplace`, logo depois (montar React).
 */
export function initSwup({ beforeReplace, afterReplace, onView }) {
  const swup = new Swup({
    containers: ['#swup'],
    animationSelector: '[class*="transition-"]',
    ignoreVisit: (url, { el } = {}) => shouldIgnore(url, el),
    plugins: [
      new SwupHeadPlugin({ persistAssets: true }),
      new SwupBodyClassPlugin(),
      new SwupPreloadPlugin(),
      new SwupA11yPlugin({ headingSelector: 'h1' }),
      new SwupScrollPlugin({
        offset: 96,
        animateScroll: { betweenPages: false, samePageWithHash: true, samePage: true },
      }),
    ],
  });

  swup.hooks.before('content:replace', beforeReplace);
  swup.hooks.on('content:replace', afterReplace);
  swup.hooks.on('page:view', onView);

  // Favoritos/seguindo mudaram: páginas pré-carregadas (ex.: /salvos/) ficaram velhas.
  window.addEventListener(DATA_EVENT, () => swup.cache.clear());

  return swup;
}
