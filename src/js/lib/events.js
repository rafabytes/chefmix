export const LOGIN_EVENT = 'chefmix:login';
export const TOAST_EVENT = 'chefmix:toast';
/** Disparado quando favoritos/seguindo mudam: o Swup limpa o cache de páginas (veja swup.js). */
export const DATA_EVENT = 'chefmix:data';

export function openLogin(reason = '') {
  window.dispatchEvent(new CustomEvent(LOGIN_EVENT, { detail: { reason } }));
}

export function toast(message, type = 'info') {
  window.dispatchEvent(new CustomEvent(TOAST_EVENT, { detail: { message, type } }));
}

export function dataChanged() {
  window.dispatchEvent(new CustomEvent(DATA_EVENT));
}
