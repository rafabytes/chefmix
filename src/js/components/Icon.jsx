import icons from '../../icons.json';

/** Ícones de traço (mesma fonte do PHP: src/icons.json). */
export default function Icon({ name, className = 'h-5 w-5', stroke = 1.8, fill = 'none', ...rest }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill={fill}
      stroke="currentColor"
      strokeWidth={stroke}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
      dangerouslySetInnerHTML={{ __html: icons[name] || '' }}
      {...rest}
    />
  );
}
