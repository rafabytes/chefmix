/**
 * Trava a rolagem da página com contador: modo cozinhar, tutorial, folhas, login e busca podem
 * abrir uns sobre os outros (e fechar em qualquer ordem) sem que um "restaure" o estado do outro.
 */
let count = 0;

export function lockScroll() {
  count += 1;
  document.documentElement.classList.add('cmx-scroll-locked');
}

export function unlockScroll() {
  count = Math.max(0, count - 1);
  if (count === 0) document.documentElement.classList.remove('cmx-scroll-locked');
}
