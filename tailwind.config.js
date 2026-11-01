/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './inc/**/*.php',
    './template-parts/**/*.php',
    './src/**/*.{js,jsx}',
  ],
  // O modo escuro só existe no modo cozinhar (preferência salva no navegador).
  darkMode: ['selector', '[data-theme="dark"]'],
  theme: {
    container: {
      center: true,
      padding: { DEFAULT: '1.25rem', sm: '1.5rem', lg: '2rem' },
      screens: { '2xl': '1280px' },
    },
    extend: {
      colors: {
        ink: {
          DEFAULT: '#16161d',
          soft: '#4b4b5a',
          mute: '#868696',
        },
        mist: {
          DEFAULT: '#f4f5fa',
          deep: '#eceef6',
        },
        line: '#e4e5ee',
        iris: {
          50: '#f3f1ff',
          100: '#e6e2ff',
          200: '#d0c9ff',
          300: '#b2a6ff',
          400: '#8f80ff',
          500: '#6f5cff',
          600: '#5a46eb',
          700: '#4836c4',
        },
        mint: {
          100: '#d9f7ee',
          300: '#8de8cd',
          400: '#4fd6b0',
          500: '#20bd94',
          700: '#0f7d63',
        },
        peach: {
          100: '#ffe7dd',
          300: '#ffc4ad',
          400: '#ffa786',
          500: '#ff8659',
          700: '#c4532a',
        },
        sky: {
          100: '#ddefff',
          300: '#a7d5ff',
          400: '#79bdff',
          500: '#4a9ff5',
        },
        lemon: {
          100: '#fff5cc',
          300: '#ffe58a',
          400: '#ffd84d',
          700: '#8a6a00',
        },
        rose: {
          100: '#ffe0e6',
          400: '#ff7a93',
          500: '#f0446a',
          700: '#b51f43',
        },
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans Variable"', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
      },
      borderRadius: {
        '4xl': '2rem',
        '5xl': '2.5rem',
      },
      boxShadow: {
        soft: '0 1px 2px rgba(22,22,40,.04), 0 12px 32px -16px rgba(22,22,60,.18)',
        float: '0 2px 6px rgba(22,22,40,.05), 0 24px 60px -24px rgba(40,30,110,.35)',
        glow: '0 10px 30px -8px rgba(111,92,255,.55)',
      },
      keyframes: {
        'fade-up': {
          '0%': { opacity: 0, transform: 'translateY(10px)' },
          '100%': { opacity: 1, transform: 'translateY(0)' },
        },
        'sheet-in': {
          '0%': { transform: 'translateY(28px) scale(.98)', opacity: 0 },
          '100%': { transform: 'translateY(0) scale(1)', opacity: 1 },
        },
        'step-in': {
          '0%': { opacity: 0, transform: 'translateX(var(--from, 24px))' },
          '100%': { opacity: 1, transform: 'translateX(0)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-8px)' },
        },
        drift: {
          '0%, 100%': { transform: 'translate3d(0,0,0) scale(1)' },
          '33%': { transform: 'translate3d(4%, -3%, 0) scale(1.06)' },
          '66%': { transform: 'translate3d(-3%, 4%, 0) scale(.96)' },
        },
        ring: {
          '0%, 100%': { transform: 'scale(1)' },
          '50%': { transform: 'scale(1.04)' },
        },
      },
      animation: {
        'fade-up': 'fade-up .45s cubic-bezier(.2,.8,.2,1) both',
        'sheet-in': 'sheet-in .3s cubic-bezier(.2,.8,.2,1) both',
        'step-in': 'step-in .35s cubic-bezier(.2,.8,.2,1) both',
        float: 'float 6s ease-in-out infinite',
        drift: 'drift 22s ease-in-out infinite',
        ring: 'ring 1s ease-in-out infinite',
      },
    },
  },
  plugins: [],
};
