import { createRoot } from 'react-dom/client';
import Recipe from './components/Recipe';
import FavoriteButton from './components/FavoriteButton';
import FollowButton from './components/FollowButton';
import ShareButton from './components/ShareButton';
import DeleteAccount from './components/DeleteAccount';

/**
 * "Ilhas" React: o PHP renderiza <div data-react="Nome" data-props='{...}'> com um fallback em HTML,
 * e o React assume o elemento. Como o Swup troca o conteúdo sem recarregar a página,
 * as ilhas são desmontadas antes e montadas de novo depois de cada navegação.
 */
const registry = { Recipe, FavoriteButton, FollowButton, ShareButton, DeleteAccount };

const roots = new Map();

export function mountIslands(scope = document) {
  if (!scope) return;
  scope.querySelectorAll('[data-react]').forEach((el) => {
    if (roots.has(el)) return;
    const Component = registry[el.dataset.react];
    if (!Component) return;

    let props = {};
    try {
      props = JSON.parse(el.dataset.props || '{}');
    } catch (error) {
      console.error(`[chefmix] props inválidas em ${el.dataset.react}`, error);
    }

    el.innerHTML = '';
    const root = createRoot(el);
    root.render(<Component {...props} />);
    roots.set(el, root);
  });
}

export function unmountIslands() {
  roots.forEach((root) => root.unmount());
  roots.clear();
}
