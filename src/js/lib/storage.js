/** localStorage com JSON e validade opcional (falha em silêncio no modo privado). */
export function load(key, fallback = null, maxAgeMs = 0) {
  try {
    const raw = window.localStorage.getItem(key);
    if (!raw) return fallback;
    const { v, t } = JSON.parse(raw);
    if (maxAgeMs && Date.now() - t > maxAgeMs) {
      window.localStorage.removeItem(key);
      return fallback;
    }
    return v ?? fallback;
  } catch (error) {
    return fallback;
  }
}

export function save(key, value) {
  try {
    window.localStorage.setItem(key, JSON.stringify({ v: value, t: Date.now() }));
  } catch (error) {
    /* sem espaço ou modo privado */
  }
}

export function remove(key) {
  try {
    window.localStorage.removeItem(key);
  } catch (error) {
    /* ignore */
  }
}

export const HOUR = 60 * 60 * 1000;
export const DAY = 24 * HOUR;
