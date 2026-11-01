import { amount } from '../lib/format';

/** "**200 g** farinha" — ou "**sal** a gosto" quando não há quantidade. */
export default function IngredientText({ qty, unit, name, strong = 'font-bold' }) {
  const value = amount(qty, unit);
  if (unit === 'a gosto' || !value) {
    return (
      <>
        <strong className={strong}>{name}</strong>
        {value && ` ${value}`}
      </>
    );
  }
  return (
    <>
      <strong className={strong}>{value}</strong> {name}
    </>
  );
}
