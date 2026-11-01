<?php
/**
 * Gera as imagens da marca a partir de assets/brand/icon.svg e do CSS compilado (dist/main.css):
 *   assets/brand/icon-512.png   → ícone do site (WordPress gera 32, 180, 192 e 270 a partir dele)
 *   assets/brand/og-default.png → imagem de compartilhamento padrão (1200×630)
 *
 * Uso: npm run build && php tools/brand.php   (precisa do Google Chrome ou Microsoft Edge instalado)
 */
require __DIR__ . '/bootstrap.php';

$dir     = dirname( __DIR__ );
$out     = $dir . '/assets/brand';
$tmp     = sys_get_temp_dir() . '/chefmix-brand';
$file    = static fn( string $path ): string => 'file:///' . ltrim( str_replace( '\\', '/', $path ), '/' );
$browser = null;

foreach ( [
	getenv( 'CHROME_PATH' ),
	'C:/Program Files/Google/Chrome/Application/chrome.exe',
	'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
	'/usr/bin/google-chrome',
	'/usr/bin/chromium',
] as $candidate ) {
	if ( $candidate && is_file( $candidate ) ) {
		$browser = $candidate;
		break;
	}
}
if ( ! $browser ) {
	cmx_cli( 'Chrome/Edge não encontrado (defina CHROME_PATH).' );
	exit( 1 );
}
if ( ! is_file( $dir . '/dist/main.css' ) ) {
	cmx_cli( 'Rode npm run build antes.' );
	exit( 1 );
}
if ( ! is_dir( $tmp ) ) {
	mkdir( $tmp, 0777, true );
}

function cmx_brand_shot( string $browser, string $html_file, string $png, int $w, int $h ): void {
	$cmd = sprintf(
		'"%s" --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 --default-background-color=00000000 --window-size=%d,%d --virtual-time-budget=3000 --screenshot=%s %s 2>&1',
		$browser,
		$w,
		$h,
		escapeshellarg( $png ),
		escapeshellarg( 'file:///' . ltrim( str_replace( '\\', '/', $html_file ), '/' ) )
	);
	exec( $cmd, $output, $code );
	$size = is_file( $png ) ? getimagesize( $png ) : false;
	if ( ! $size || $size[0] !== $w || $size[1] !== $h ) {
		cmx_cli( "Falhou: {$png}\n" . implode( "\n", $output ) );
		exit( 1 );
	}
	cmx_cli( sprintf( '✓ %s (%d×%d, %d KB)', basename( $png ), $w, $h, filesize( $png ) / 1024 ) );
}

$page = static fn( string $body, string $css = '' ): string => '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
	. '<link rel="stylesheet" href="' . $file( $dir . '/dist/main.css' ) . '">'
	. '<style>html,body{margin:0;overflow:hidden}' . $css . '</style></head><body>' . $body . '</body></html>';

/* ------------------------------------------------------------------ Ícone */

file_put_contents( "$tmp/icon.html", $page(
	'<img src="' . $file( "$out/icon.svg" ) . '" width="512" height="512" style="display:block">',
	'body{background:transparent}'
) );
cmx_brand_shot( $browser, "$tmp/icon.html", "$out/icon-512.png", 512, 512 );

/* ------------------------------------------------------ Compartilhamento */

ob_start();
?>
<div class="og">
	<span class="blob" style="left:-120px;top:-180px;width:620px;height:620px;background:#d0c9ff"></span>
	<span class="blob" style="right:-160px;top:-60px;width:560px;height:560px;background:#d9f7ee"></span>
	<span class="blob" style="left:300px;bottom:-320px;width:640px;height:640px;background:#ffe7dd"></span>
	<span class="blob" style="right:180px;bottom:-200px;width:360px;height:360px;background:#ddefff"></span>

	<div class="copy">
		<div class="brand">
			<img src="<?php echo esc_attr( $file( "$out/favicon.svg" ) ); ?>" width="64" height="64" alt="">
			<span>chef<span class="text-gradient">mix</span></span>
		</div>
		<h1>Cozinhe sem<br>adivinhar <span class="text-gradient">nada.</span></h1>
		<p>Receitas guiadas passo a passo, com panela, frigideira e forno de casa.</p>
		<span class="url">rafabytes.com/chefmix</span>
	</div>

	<div class="card glass rounded-5xl">
		<div class="flex gap-1.5">
			<?php for ( $i = 0; $i < 8; $i++ ) : ?>
				<span class="h-1.5 flex-1 rounded-full <?php echo $i < 2 ? 'bg-ink' : ( 2 === $i ? 'bg-gradient-to-r from-iris-500 to-mint-400' : 'bg-ink/10' ); ?>"></span>
			<?php endfor; ?>
		</div>
		<div class="mt-5 flex items-center justify-between text-sm font-semibold text-ink-mute">
			<span>Passo 3 de 8</span>
			<span class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1 text-ink-soft"><?php echo cmx_icon( 'pot', 'h-4 w-4' ); // phpcs:ignore ?> Na panela</span>
		</div>
		<p class="mt-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Refogue a cebola</p>
		<div class="mt-6 grid grid-cols-3 gap-2.5">
			<div class="dial"><span class="dial-label"><?php echo cmx_icon( 'flame', 'h-4 w-4' ); // phpcs:ignore ?>Fogo</span><span class="dial-value !text-2xl sm:!text-3xl">Médio</span></div>
			<div class="dial"><span class="dial-label"><?php echo cmx_icon( 'timer', 'h-4 w-4' ); // phpcs:ignore ?>Tempo</span><span class="dial-value">3:00</span></div>
			<div class="dial"><span class="dial-label"><?php echo cmx_icon( 'stir', 'h-4 w-4' ); // phpcs:ignore ?>Mexer</span><span class="dial-value !text-lg sm:!text-xl">Às vezes</span></div>
		</div>
		<p class="mt-5 flex items-center gap-2 rounded-2xl bg-mint-100 px-4 py-2.5 text-sm font-semibold text-mint-700">
			<?php echo cmx_icon( 'eye', 'h-4 w-4 shrink-0' ); // phpcs:ignore ?> Pronto quando a cebola estiver transparente.
		</p>
	</div>
</div>
<?php
$og_body = ob_get_clean();

file_put_contents( "$tmp/og.html", $page( $og_body, '
	body{background:#f4f5fa}
	.og{position:relative;width:1200px;height:630px;overflow:hidden;font-family:"Plus Jakarta Sans Variable",system-ui,sans-serif;color:#16161d}
	.og .blob{position:absolute;border-radius:9999px;filter:blur(70px);opacity:.9}
	.og .copy{position:absolute;left:84px;top:78px;width:560px}
	.og .brand{display:flex;align-items:center;gap:16px;font-size:44px;font-weight:800;letter-spacing:-.03em}
	.og .brand img{border-radius:9999px;box-shadow:0 10px 24px -10px rgba(111,92,255,.7)}
	.og h1{margin:46px 0 0;font-size:78px;line-height:1;font-weight:800;letter-spacing:-.04em}
	.og p{margin:28px 0 0;font-size:27px;line-height:1.4;color:#4b4b5a;max-width:520px}
	.og .url{display:inline-block;margin-top:34px;padding:10px 22px;border-radius:9999px;background:rgba(255,255,255,.7);font-size:21px;font-weight:700;color:#5a46eb}
	.og .card{position:absolute;right:72px;top:96px;width:440px;padding:32px;transform:rotate(2deg)}
' ) );
cmx_brand_shot( $browser, "$tmp/og.html", "$out/og-default.png", 1200, 630 );
