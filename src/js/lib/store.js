import { useSyncExternalStore } from 'react';

/**
 * Estado do usuário logado (window.CHEFMIX.user) compartilhado pelas ilhas.
 * Favoritos e "seguindo" vêm daqui — e não do HTML — para que páginas em cache do Swup
 * nunca mostrem um coração ou botão "Seguir" desatualizado.
 */
const listeners = new Set();

export const getUser = () => (window.CHEFMIX && window.CHEFMIX.user) || null;

function subscribe(listener) {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

export function setUserList(key, list) {
  const user = getUser();
  if (!user) return;
  window.CHEFMIX.user = { ...user, [key]: list.map(Number) };
  listeners.forEach((listener) => listener());
}

/** Seletor reativo: `useUser((u) => u?.favorites.includes(id))`. */
export function useUser(selector = (user) => user) {
  return useSyncExternalStore(subscribe, () => selector(getUser()));
}
