const cfg = () => window.CHEFMIX || {};

/**
 * Nonce novo pelo admin-ajax do WordPress (ação nativa "rest-nonce", só para quem está logado).
 * A página pode ter vindo do cache do LiteSpeed com um nonce vencido — sem isso a REST responde
 * 403 "Cookie check failed".
 */
async function refreshNonce() {
  const url = `${cfg().ajaxUrl || '/wp-admin/admin-ajax.php'}?action=rest-nonce`;
  const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
  const nonce = (await response.text()).trim();
  if (!response.ok || !/^[a-f0-9]{10}$/i.test(nonce)) return false;
  window.CHEFMIX = { ...cfg(), nonce };
  return true;
}

/** Chamada à REST API do ChefMix (autenticada pelo cookie + nonce do WordPress). */
export async function api(path, { method = 'GET', body } = {}, retried = false) {
  const base = cfg().restUrl || '/wp-json/chefmix/v1/';
  const headers = {};
  // Visitante não precisa de nonce (login/cadastro são públicos); mandar um vencido daria 403.
  if (cfg().user && cfg().nonce) headers['X-WP-Nonce'] = cfg().nonce;
  if (body) headers['Content-Type'] = 'application/json';

  const response = await fetch(base + path.replace(/^\//, ''), {
    method,
    credentials: 'same-origin',
    cache: 'no-store',
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (!retried && data.code === 'rest_cookie_invalid_nonce' && (await refreshNonce().catch(() => false))) {
      return api(path, { method, body }, true);
    }
    const error = new Error(data.message || 'Algo deu errado. Tente novamente.');
    error.status = response.status;
    error.code = data.code;
    throw error;
  }
  return data;
}
