# ChefMix — tema WordPress

Receitas guiadas e inteligentes para quem cozinha com utensílios comuns — panela, frigideira, tigela, liquidificador, forno. Começa pela **mise en place** e, depois, cada passo mostra **onde fazer**, **com quais ingredientes** (já recalculados para as porções escolhidas), **como regular** (fogo, forno, velocidade, mexer, tampa), **por quanto tempo** e **como saber que está pronto**. Nada pressupõe um aparelho específico.

Stack: **WordPress + Parcel + TailwindCSS + Swup.js + React** (ACF PRO para os campos).

## Primeiros passos

```bash
cd wp-content/themes/chefemix-theme
npm install
npm run build          # limpa dist/ e .parcel-cache e gera dist/ (produção)
npm run dev            # parcel watch: recompila ao salvar

php tools/setup.php    # ativa ACF PRO + tema, links permanentes /%postname%/
php tools/seed-demo.php  # 8 cozinhas, 3 chefs, 1 assinante e 10 receitas (pode rodar de novo)
```

Usuários de demonstração (senha `chefmix-demo`): `chef-marina`, `chef-lorenzo`, `chef-camille` (Chef) e `assinante-demo` (Assinante). **Remova antes de ir para produção.**

> **Parcel:** se `dist/` parecer desatualizado, rode `npm run build` (ele já limpa `dist/` e `.parcel-cache`).

## Produção em `https://rafabytes.com/chefmix`

O tema não tem caminhos fixos: tudo sai de `home_url()`, `rest_url()` e de URLs relativas no CSS (`publicUrl: "./"`), então funciona em subdiretório.

1. Instale o WordPress em `/chefmix` (ou migre com o All-in-One WP Migration, já presente nos plugins). Endereços do site: `https://rafabytes.com/chefmix`.
2. Envie o tema **com** a pasta `dist/` (não precisa de `node_modules/` no servidor) — de preferência pelo deploy automático abaixo.
3. Ative **ACF PRO** e o tema. Em **Configurações → Links permanentes**, escolha "Nome do post" e salve — o `.htaccess` gerado terá `RewriteBase /chefmix/`.
4. Em **Configurações → ChefMix**, cole o Client ID do Google (abaixo).
5. Apague os usuários de demonstração e, no `wp-config.php`, desligue `WP_DEBUG`.

O site **precisa de HTTPS** em produção: o navegador só libera *tela sempre ligada* (Wake Lock), *microfone* (comandos de voz) e o botão do Google em contexto seguro.

### Deploy (GitHub → cPanel)

Repositório: `github.com/rafabytes/chefmix` (a raiz do repo é a pasta do tema), clonado no cPanel em **Git Version Control** (`~/repositories/chefmix`). O servidor não roda Node: o `dist/` é versionado, então **rode `npm run build` e commite o `dist/` antes de cada push**.

Para publicar: `git push` → no cPanel, **Manage → Pull or Deploy → Update from Remote → Deploy HEAD Commit**.

O [.cpanel.yml](.cpanel.yml) copia o tema com `rsync` para `~/public_html/chefmix/wp-content/themes/chefemix-theme/`, apaga lá o que saiu do repositório e força pastas `755` / arquivos `644`. Não vão para o servidor: `src/js`, `src/css`, `node_modules`, `package*.json`, configs e este README (`src/icons.json` vai — o PHP lê os ícones dele). O clone do cPanel não pode ter alterações locais, senão o deploy fica bloqueado.

O nome da pasta no servidor precisa continuar `chefemix-theme` (é o que o WordPress guarda como tema ativo).

**Assets sem 403:** além das permissões do deploy, o [.htaccess](.htaccess) do tema libera explicitamente CSS/JS/fontes/imagens (sobrepõe bloqueios herdados), corrige o MIME do `.woff2`, manda CORS nas fontes (CDN, www ↔ sem www) e bloqueia só arquivos de desenvolvimento.

### Ícone do site, favicon e SEO

Arquivos da marca em [assets/brand/](assets/brand/):

| Arquivo | Uso |
|---|---|
| `icon.svg` → `icon-512.png` | **Ícone do site** do WordPress (Configurações → Geral). O WordPress gera 32, 180 (Apple), 192 e 270 px. |
| `favicon.svg` | Favicon vetorial das abas (site, painel e login). |
| `og-default.png` (1200×630) | Imagem de compartilhamento padrão (WhatsApp, redes sociais). Receitas com foto usam a própria foto. |

O ícone do site é cadastrado sozinho na primeira vez que um administrador abre o painel (ou com `php tools/setup.php`) — só se ainda não houver um; depois pode ser trocado normalmente em Configurações → Geral. Para regerar os PNGs depois de mexer no SVG ou no visual: `npm run build && php tools/brand.php` (usa o Chrome/Edge instalado).

[inc/seo.php](inc/seo.php) imprime descrição, Open Graph e Twitter Card (receita, chef, cozinha, listas) e marca `/salvos/` como `noindex`. Se um plugin de SEO (Yoast, Rank Math, AIOSEO, SEOPress, The SEO Framework) for ativado, o tema deixa as metatags com ele. Vale preencher o **Descrição** em Configurações → Geral — sem ela o tema usa um texto padrão.

### LiteSpeed Cache

O tema já se protege das otimizações que quebrariam o React/Swup ([inc/cache.php](inc/cache.php)):

- `dist/main.js`, `dist/main.css` e o `window.CHEFMIX` saem com `data-no-optimize` / `data-no-defer` e entram nas listas de exclusão via filtro: nada de minificar de novo, combinar, adiar/atrasar ou **UCSS** (o UCSS removeria classes que só existem nos componentes React — login, modo cozinhar, avisos).
- Páginas de quem está logado só vão para cache **privado**, mesmo com ESI ligado (o cabeçalho e o `window.CHEFMIX.user` são da pessoa). `/salvos/` e a REST do tema nunca entram em cache.
- Favoritar/seguir limpa só as páginas que mostram aquele contador (tags `cmx_r_{id}` / `cmx_chef_{id}`) e o cache privado da pessoa.
- **Nonce vencido em página cacheada** (o 403 "A verificação do cookie falhou"): visitantes não mandam nonce (login/cadastro são públicos) e, logado, o front pede um nonce novo ao `admin-ajax.php?action=rest-nonce` e repete a chamada uma vez.
- Excluir a conta usa `POST` — o LiteSpeed só troca o cookie de sessão do cache em GET/POST.

Configuração recomendada (o resto pode ficar no padrão ou no preset "Avançado"):

| Onde | Opção | Valor |
|---|---|---|
| Cache | Cache Logged-in Users | ligado ou desligado (os dois funcionam) |
| Cache | Cache REST API | pode ficar ligado |
| Page Optimization → CSS | CSS Minify / Combine / UCSS / Load CSS Async | pode ligar (o CSS do tema fica de fora) |
| Page Optimization → JS | JS Minify / Combine / Load JS Deferred ou **Delayed** | pode ligar (o JS do tema fica de fora) |
| Cache → Advanced | **Instant Click** | **desligado** — o Swup já pré-carrega e navega sem recarregar |
| Page Optimization → Media | Lazy Load Images | pode ligar (o LiteSpeed acompanha o conteúdo trocado pelo Swup) |

Depois de um deploy não é preciso limpar o cache à mão: quando o `dist/main.js` muda, a primeira requisição dispara um **Purge All**.

Se usar QUIC.cloud CDN, o CORS das fontes já está no `.htaccess`.

## Login

### Google (botão oficial, sem redirecionamento OAuth)

Usa o **Google Identity Services**: o botão abre um popup do Google e devolve um *ID token* assinado para o JavaScript, que o envia por `POST /wp-json/chefmix/v1/auth/google`. O servidor confere o token no endpoint `tokeninfo` do Google (assinatura, validade, público = nosso Client ID, e-mail verificado).

Não existe callback com `?code=&state=` na URL nem chave secreta — o fluxo que costuma ser barrado por regras de ModSecurity que não podem ser personalizadas.

1. Google Cloud Console → **APIs e serviços → Credenciais → Criar credenciais → ID do cliente OAuth → Aplicativo da Web**.
2. Em **Origens JavaScript autorizadas**: `https://rafabytes.com` (origem é só domínio, sem `/chefmix`).
3. Não é preciso URI de redirecionamento. Copie o **Client ID** para **Configurações → ChefMix** (ou constante `CHEFMIX_GOOGLE_CLIENT_ID` no `wp-config.php`).

> O Google só aceita `http://` para `localhost`. Em `http://chefmix.local` o botão não funciona; teste em produção ou num endereço `http://localhost`.

### E-mail e senha

Cadastro e login pelo próprio modal (`/auth/register` e `/auth/login`), com limite de tentativas por IP e campo-isca contra robôs. "Esqueci minha senha" usa o fluxo nativo do WordPress. O cadastro por e-mail pode ser desligado em **Configurações → ChefMix**.

Contas de chef/administrador nunca são vinculadas ao Google pelo e-mail.

## Usuários e permissões

| Papel | O que faz |
|---|---|
| **Administrador** | Único que **cria** receitas. Cadastra chefs (Usuários → Adicionar, função "Chef") e atribui cada receita a um chef na caixa **Chef responsável**. Publica. |
| **Chef** (`chef`) | Edita **só as receitas atribuídas a ele** (é o autor). Não cria, não exclui, não publica rascunhos (envia para revisão). Vê só a própria mídia. wp-admin enxuto. Tem **perfil público** em `/chef/slug/` com foto, especialidade, cidade, Instagram, bio e as receitas dele. |
| **Assinante** | Só **favorita** receitas e **segue** chefs. Sem perfil público, **sem foto** (o tema não guarda a foto do Google e o avatar é bloqueado), sem wp-admin e sem barra de administração. Vê tudo em `/salvos/` e pode excluir a própria conta. |

Permissões próprias (`capability_type` `receita`): `create_receitas` só do administrador; o chef tem `edit_receitas` + `edit_published_receitas`.

## Modelo de conteúdo

- **Receita** (`receita`, `/receitas/slug/`): frase de apresentação (resumo), rendimento, dificuldade, tempos, utensílios, ingredientes (com grupos) e **passos**.
- **Mise en place**: cada ingrediente tem quantidade, unidade e **Preparo** ("picada fina", "em cubos", "em temperatura ambiente"). É a primeira etapa do modo cozinhar.
- **Passo**: onde acontece (bancada, tigela, panela, frigideira, liquidificador, processador, batedeira, forno, geladeira, descanso, servir), ação curta ("Refogue a cebola"), detalhes, **ingredientes do passo** (escalam com as porções), tempo, **fogo** (baixo/médio/alto), **forno** (°C), **velocidade** (pulsar/baixa/média/alta), **mexer** (sem parar / às vezes / não mexa), **tampa**, **Pronto quando…** (o sinal para avançar), dica, atenção e foto opcional. Os campos aparecem conforme o local escolhido.
- **Cozinha** (`cozinha`, `/cozinha/slug/`): cor e ícone da capa (receitas sem foto ganham uma capa em degradê com um orbe de vidro).
- Rotas próprias: `/chefs/` e `/salvos/`.

## Orientação ao usuário (como a pessoa sabe exatamente o que fazer)

Premissa: a pessoa tem uma cozinha comum e talvez nunca tenha feito a receita. Nada de jargão de aparelho.

**Na página da receita**
1. **Porções**: o seletor recalcula tudo, arredondando para medidas reais (5 g, ¼ de xícara, ½ unidade).
2. **Mise en place**: checklist com o *preparo* de cada ingrediente em destaque ("picada fina") e barra de progresso ("7 de 12 prontos"), salvo no navegador.
3. **Visão geral**: cada passo com onde acontece, fogo/forno/tempo/mexer e o "pronto quando"; tocar num passo começa a partir dele.
4. **Botão no polegar**: barra fixa "Começar" ou "Continuar do passo N" (o progresso fica salvo por 7 dias).

**No modo cozinhar (tela cheia)**
5. **Começa pela mise en place**: separar utensílios e deixar cada ingrediente medido e preparado antes de ligar o fogo; só então "Começar o passo 1".
6. **Um passo por tela** e sempre na mesma ordem visual: **onde** (Na panela / No forno / No liquidificador…) → **ação em letras grandes** → ingredientes do passo ("Coloque na panela", toque para riscar) → **ajustes grandes** (fogo com chamas acesas, forno, velocidade, tempo, mexer, tampa) → timer → **"Pronto quando…"** em destaque → detalhes, atenção e dica.
7. **Navegação à prova de mão suja**: botões grandes na base da tela, deslizar para os lados, setas/espaço no teclado, e o "voltar" do celular fecha o modo. Sem "toque em qualquer lugar", para evitar avanços acidentais.
8. **Onde estou**: barra segmentada (mise en place + passos) e "Passo 3 de 8 · ~22 min"; tocar abre a lista para pular. O rodapé mostra **"A seguir"** para a pessoa já se preparar.
9. **Timers**: um toque inicia; continuam rodando ao trocar de passo (aparecem como pílulas) e avisam com som, vibração e um aviso fixo com "Ver".
10. **Tela sempre ligada** (Wake Lock), com indicador.
11. **Mãos livres**: comandos de voz ("próximo", "voltar", "repetir", "iniciar timer", "pausar", "ingredientes") e leitura do passo em voz alta, incluindo o "pronto quando" — ambos opcionais em Ajustes.
12. **Conforto**: tema escuro e três tamanhos de texto.
13. **Primeira vez**: tutorial curto que ensina o *sistema* (mise en place, navegação, fogo/tempo/ponto, timers, voz); pode ser revisto em Ajustes.
14. **Fim**: "Bom apetite!" com favoritar e seguir o chef.

**Para quem escreve receitas** (mensagens no editor): mise en place no campo *Preparo*; um passo = uma ação, começando pelo verbo; quantidades em *Ingredientes deste passo*; fogo/forno/velocidade nos campos próprios; e sempre o *Pronto quando…*.

## Como o front-end funciona

| Peça | Papel |
|---|---|
| **Parcel** | `src/js/main.jsx` → `dist/main.js` + `dist/main.css` (nomes fixos, versionados por `filemtime`). |
| **Tailwind** | Tokens em `tailwind.config.js`; componentes (vidro, mostradores, chips) em `src/css/main.css`. |
| **Swup** | Navegação sem recarregar (Head, Body Class, Preload, A11y, Scroll). Favoritar/seguir limpa o cache do Swup. |
| **React** | "Ilhas": o PHP imprime `<div data-react="Nome" data-props='{…}'>` com fallback em HTML (`cmx_island()`). Ilhas: `Recipe` (mise en place + modo cozinhar), `FavoriteButton`, `FollowButton`, `ShareButton`, `DeleteAccount`. Login e avisos ficam numa raiz global fora do `#swup`. |
| **Ícones** | `src/icons.json` é a fonte única para PHP (`cmx_icon()`) e React (`<Icon>`). |

Linguagem visual: base clara e fria com aurora pastel ao fundo (referência inZOI) e material de vidro líquido — translúcido, desfoque + saturação, borda de luz e brilho especular (referência Liquid Glass) — só nas superfícies flutuantes.

## Observações

- O sistema é de receita guiada com utensílios comuns; não cita nem pressupõe nenhuma marca de aparelho. O termo genérico usado quando o utensílio não é específico é "recipiente".
- O ACF PRO instalado (6.0.6) é antigo e gera avisos *deprecated* no PHP 8.2 — atualize-o.
- `WP_DEBUG` está ligado no `wp-config.php` local (log em `wp-content/debug.log`, sem exibir na tela).
