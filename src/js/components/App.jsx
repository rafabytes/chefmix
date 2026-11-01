import LoginModal from './LoginModal';
import Toaster from './Toaster';

/** Raiz global (fora do contêiner do Swup): login e avisos persistem entre as navegações. */
export default function App() {
  return (
    <>
      <LoginModal />
      <Toaster />
    </>
  );
}
