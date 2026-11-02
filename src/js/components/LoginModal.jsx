import { useEffect, useRef, useState } from 'react';
import { LOGIN_EVENT, toast } from '../lib/events';
import { useDialog } from '../lib/useDialog';
import { api } from '../lib/api';
import Icon from './Icon';

const cfg = () => window.CHEFMIX || {};

/* ------------------------------------------------------------------ Google */

let gisPromise = null;

/** Carrega o Google Identity Services uma única vez. */
function loadGoogle() {
  if (window.google?.accounts?.id) return Promise.resolve();
  if (!gisPromise) {
    gisPromise = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = 'https://accounts.google.com/gsi/client';
      script.async = true;
      script.onload = resolve;
      script.onerror = () => {
        gisPromise = null;
        reject(new Error('Não foi possível carregar o login do Google.'));
      };
      document.head.appendChild(script);
    });
  }
  return gisPromise;
}

/**
 * Botão oficial "Continuar com o Google". O Google devolve um ID token (JWT) para o callback,
 * que é enviado à REST API — sem redirecionamentos nem parâmetros OAuth na URL.
 */
function GoogleButton({ onSuccess, onError }) {
  const ref = useRef(null);
  const [state, setState] = useState('loading');
  const clientId = cfg().google;

  useEffect(() => {
    if (!clientId) return undefined;
    let cancelled = false;

    loadGoogle()
      .then(() => {
        if (cancelled || !ref.current) return;
        window.google.accounts.id.initialize({
          client_id: clientId,
          ux_mode: 'popup',
          auto_select: false,
          cancel_on_tap_outside: true,
          context: 'signin',
          callback: async ({ credential }) => {
            try {
              await api('auth/google', { method: 'POST', body: { credential } });
              onSuccess();
            } catch (error) {
              onError(error.message);
            }
          },
        });
        window.google.accounts.id.renderButton(ref.current, {
          type: 'standard',
          theme: 'outline',
          size: 'large',
          shape: 'pill',
          text: 'continue_with',
          logo_alignment: 'center',
          locale: 'pt-BR',
          width: Math.min(400, ref.current.offsetWidth || 320),
        });
        setState('ready');
      })
      .catch((error) => {
        if (!cancelled) {
          setState('error');
          onError(error.message);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [clientId]);

  if (!clientId) return null;

  return (
    <div className="relative flex min-h-[44px] justify-center">
      {state === 'loading' && <div className="absolute inset-0 animate-pulse rounded-full bg-white/70 ring-1 ring-ink/10" />}
      <div ref={ref} className="flex w-full justify-center" />
    </div>
  );
}

/* ------------------------------------------------------------------- Modal */

function Field({ label, icon, ...props }) {
  return (
    <label className="block">
      <span className="label">{label}</span>
      <span className="relative block">
        <Icon name={icon} className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-mute" />
        <input className="input pl-12" {...props} />
      </span>
    </label>
  );
}

export default function LoginModal() {
  const [state, setState] = useState({ open: false, reason: '' });
  const [mode, setMode] = useState('login');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [showPass, setShowPass] = useState(false);
  const close = () => setState((s) => ({ ...s, open: false }));
  const ref = useDialog(state.open, close, { autoFocus: false });

  const open = (reason = '') => {
    setError('');
    setState({ open: true, reason });
  };

  useEffect(() => {
    const onOpen = (event) => open(event.detail?.reason || '');
    window.addEventListener(LOGIN_EVENT, onOpen);

    // Delegação: qualquer elemento com [data-login-open] (inclusive no PHP) abre o modal.
    const onClick = (event) => {
      const trigger = event.target.closest?.('[data-login-open]');
      if (!trigger) return;
      event.preventDefault();
      open(trigger.dataset.loginReason || '');
    };
    document.addEventListener('click', onClick);

    return () => {
      window.removeEventListener(LOGIN_EVENT, onOpen);
      document.removeEventListener('click', onClick);
    };
  }, []);

  const success = () => {
    toast('Pronto, você entrou!', 'success');
    window.setTimeout(() => window.location.reload(), 350);
  };

  const submit = async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    setBusy(true);
    setError('');
    try {
      if (mode === 'login') {
        await api('auth/login', { method: 'POST', body: { login: form.get('email'), password: form.get('password') } });
      } else {
        await api('auth/register', {
          method: 'POST',
          body: { name: form.get('name'), email: form.get('email'), password: form.get('password'), company: form.get('company') },
        });
      }
      success();
    } catch (err) {
      setError(err.message);
      setBusy(false);
    }
  };

  if (!state.open) return null;

  const canRegister = cfg().register !== false;
  const hasGoogle = Boolean(cfg().google);
  const isLogin = mode === 'login' || !canRegister;

  return (
    <div className="fixed inset-0 z-[110] flex items-end justify-center bg-ink/30 backdrop-blur-sm sm:items-center sm:p-4" onMouseDown={(e) => e.target === e.currentTarget && close()}>
      <div
        ref={ref}
        role="dialog"
        aria-modal="true"
        aria-labelledby="login-title"
        tabIndex={-1}
        className="glass glass-solid max-h-[92vh] w-full max-w-md animate-sheet-in overflow-y-auto rounded-t-5xl p-7 pb-[max(1.75rem,env(safe-area-inset-bottom))] outline-none sm:rounded-5xl"
      >
        <div className="flex items-start justify-between gap-4">
          <div>
            <h2 id="login-title" className="text-3xl font-extrabold tracking-tight">
              {isLogin ? 'Entrar' : 'Criar conta'}
            </h2>
            <p className="mt-2 text-ink-soft">{state.reason || 'Salve receitas e siga seus chefs favoritos.'}</p>
          </div>
          <button type="button" onClick={close} aria-label="Fechar" className="icon-btn -mr-2 -mt-2 shrink-0">
            <Icon name="x" />
          </button>
        </div>

        {hasGoogle && (
          <>
            <div className="mt-7">
              <GoogleButton onSuccess={success} onError={setError} />
            </div>
            <div className="my-6 flex items-center gap-3 text-xs font-semibold uppercase tracking-wider text-ink-mute">
              <span className="h-px flex-1 bg-ink/10" /> ou com e-mail <span className="h-px flex-1 bg-ink/10" />
            </div>
          </>
        )}

        <form onSubmit={submit} className={`space-y-4 ${hasGoogle ? '' : 'mt-7'}`} noValidate>
          {!isLogin && <Field label="Seu nome" icon="user" name="name" autoComplete="name" required minLength={2} />}
          <Field label="E-mail" icon="mail" name="email" type="email" autoComplete="email" inputMode="email" required />
          <div>
            <Field label="Senha" icon="lock" name="password" type={showPass ? 'text' : 'password'} autoComplete={isLogin ? 'current-password' : 'new-password'} required minLength={isLogin ? 1 : 8} />
            <div className="mt-2 flex items-center justify-between text-sm">
              <button type="button" className="font-semibold text-ink-mute hover:text-ink" onClick={() => setShowPass((v) => !v)}>
                {showPass ? 'Ocultar senha' : 'Mostrar senha'}
              </button>
              {isLogin ? (
                <a className="font-semibold text-iris-600 hover:underline" href={cfg().lostPassword} data-no-swup>
                  Esqueci minha senha
                </a>
              ) : (
                <span className="text-ink-mute">mínimo de 8 caracteres</span>
              )}
            </div>
          </div>
          {/* Isca para robôs: invisível para pessoas. */}
          {!isLogin && (
            <input type="text" name="company" tabIndex={-1} autoComplete="off" className="absolute -left-[9999px] h-0 w-0 opacity-0" aria-hidden="true" />
          )}

          {error && (
            <p role="alert" className="flex items-start gap-2 rounded-2xl bg-rose-100 px-4 py-3 text-sm font-medium text-rose-700">
              <Icon name="alert" className="mt-0.5 h-4 w-4 shrink-0" /> {error}
            </p>
          )}

          <button type="submit" className="btn btn-primary btn-lg w-full" disabled={busy}>
            {busy ? 'Aguarde…' : isLogin ? 'Entrar' : 'Criar minha conta'}
          </button>
        </form>

        {canRegister && (
          <p className="mt-5 text-center text-sm text-ink-soft">
            {isLogin ? 'Ainda não tem conta? ' : 'Já tem conta? '}
            <button
              type="button"
              className="font-semibold text-iris-600 hover:underline"
              onClick={() => {
                setMode(isLogin ? 'register' : 'login');
                setError('');
              }}
            >
              {isLogin ? 'Criar conta' : 'Entrar'}
            </button>
          </p>
        )}

        <p className="mt-5 text-center text-xs leading-relaxed text-ink-mute">
          Sua conta serve só para salvar receitas e seguir chefs. Não pedimos foto e não publicamos nada em seu nome.
          {cfg().termsUrl && (
            <>
              {' '}
              <a className="underline" href={cfg().termsUrl}>
                Termos de uso
              </a>
            </>
          )}
          {cfg().privacyUrl && (
            <>
              {' '}
              <a className="underline" href={cfg().privacyUrl}>
                Privacidade
              </a>
            </>
          )}
        </p>
      </div>
    </div>
  );
}
