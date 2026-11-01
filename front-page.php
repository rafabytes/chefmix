<?php
/**
 * Página inicial.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$recipes = get_posts( [ 'post_type' => 'receita', 'numberposts' => 6, 'orderby' => 'date', 'order' => 'DESC' ] );
$chefs   = get_users( [ 'role' => 'chef', 'number' => 4, 'orderby' => 'post_count', 'order' => 'DESC' ] );
$archive = get_post_type_archive_link( 'receita' );
?>

<!-- Hero -->
<section class="container grid items-center gap-12 pt-10 sm:pt-16 lg:grid-cols-[1.05fr_.95fr] lg:gap-8 lg:pt-20">
	<div class="animate-fade-up">
		<span class="pill-glass"><span class="h-2 w-2 rounded-full bg-mint-500"></span> Cozinha guiada, passo a passo</span>
		<h1 class="mt-6 text-[2.6rem] font-extrabold leading-[1.02] tracking-tight sm:text-6xl lg:text-7xl">
			Cozinhe sem<br>adivinhar <span class="text-gradient">nada.</span>
		</h1>
		<p class="mt-6 max-w-xl text-lg leading-relaxed text-ink-soft sm:text-xl">
			Primeiro você separa e prepara tudo. Depois, cada passo mostra só o que importa agora: onde fazer, quanto usar, <strong class="font-semibold text-ink">em que fogo, por quanto tempo e como saber que ficou pronto</strong>. Com timer, tela sempre ligada e comandos de voz.
		</p>
		<div class="mt-9 flex flex-wrap gap-3">
			<a href="<?php echo esc_url( $archive ); ?>" class="btn btn-accent btn-lg">Escolher uma receita <?php echo cmx_icon( 'arrow-right', 'h-5 w-5' ); // phpcs:ignore ?></a>
			<a href="#como-funciona" class="btn btn-glass btn-lg">Como funciona</a>
		</div>
	</div>

	<!-- Prévia do modo cozinhar -->
	<div class="relative mx-auto w-full max-w-md lg:max-w-none" aria-hidden="true">
		<div class="absolute -inset-6 rounded-[3rem] bg-gradient-to-br from-iris-200/70 via-sky-100/60 to-mint-100/70 blur-2xl"></div>
		<div class="glass relative rounded-5xl p-6 sm:p-8">
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

			<div class="mt-5 flex flex-wrap gap-2">
				<span class="ing-chip"><b>30 g</b> azeite</span>
				<span class="ing-chip"><b>1</b> cebola picada</span>
				<span class="ing-chip"><b>2</b> dentes de alho</span>
			</div>

			<p class="mt-4 flex items-center gap-2 rounded-2xl bg-mint-100 px-4 py-2.5 text-sm font-semibold text-mint-700">
				<?php echo cmx_icon( 'eye', 'h-4 w-4 shrink-0' ); // phpcs:ignore ?> Pronto quando a cebola estiver transparente.
			</p>

			<div class="mt-7 flex items-center gap-3">
				<span class="btn btn-glass h-14 w-14 shrink-0 p-0"><?php echo cmx_icon( 'chevron-left', 'h-6 w-6' ); // phpcs:ignore ?></span>
				<span class="btn btn-primary h-14 flex-1 text-base">Próximo passo <?php echo cmx_icon( 'arrow-right', 'h-5 w-5' ); // phpcs:ignore ?></span>
			</div>
		</div>

		<div class="glass absolute -top-5 left-8 hidden items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold animate-float sm:flex">
			<?php echo cmx_icon( 'eye', 'h-4 w-4 text-mint-500' ); // phpcs:ignore ?> Tela sempre ligada
		</div>
		<div class="glass absolute -right-3 bottom-28 hidden items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold animate-float [animation-delay:-3s] sm:flex">
			<?php echo cmx_icon( 'mic', 'h-4 w-4 text-iris-500' ); // phpcs:ignore ?> “Próximo”
		</div>
	</div>
</section>

<!-- Cozinhas -->
<section class="container mt-20">
	<?php get_template_part( 'template-parts/cuisine-chips' ); ?>
</section>

<!-- Receitas -->
<?php if ( $recipes ) : ?>
	<section class="container mt-10">
		<div class="flex items-end justify-between gap-4">
			<div>
				<p class="eyebrow">Para cozinhar hoje</p>
				<h2 class="section-title">Receitas guiadas</h2>
			</div>
			<a href="<?php echo esc_url( $archive ); ?>" class="btn btn-glass hidden sm:inline-flex">Ver todas</a>
		</div>
		<div class="mt-8 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
			<?php
			foreach ( $recipes as $recipe ) {
				get_template_part( 'template-parts/recipe-card', null, [ 'post' => $recipe ] );
			}
			?>
		</div>
		<a href="<?php echo esc_url( $archive ); ?>" class="btn btn-glass mt-8 w-full sm:hidden">Ver todas as receitas</a>
	</section>
<?php endif; ?>

<!-- Como funciona -->
<section id="como-funciona" class="container mt-28 scroll-mt-28">
	<div class="max-w-2xl">
		<p class="eyebrow">Como funciona</p>
		<h2 class="section-title">Feito para quem está com a mão na massa.</h2>
		<p class="mt-4 text-lg text-ink-soft">Você olha para a tela por dois segundos e já sabe o que fazer. Sem rolar, sem procurar, sem tocar com a mão suja.</p>
	</div>

	<?php
	$features = [
		[ 'icon' => 'board', 'tone' => 'iris', 'title' => 'Mise en place primeiro', 'text' => 'Antes de ligar o fogo, meça, pique e separe cada ingrediente — marcando um a um. As quantidades se ajustam às porções que você escolher.' ],
		[ 'icon' => 'tap', 'tone' => 'mint', 'title' => 'Um passo por tela', 'text' => 'Uma ação de cada vez, em letras grandes. Avance pelo botão no alcance do polegar ou deslizando a tela.' ],
		[ 'icon' => 'flame', 'tone' => 'peach', 'title' => 'Fogo, tempo e ponto', 'text' => 'Cada passo diz onde fazer — panela, frigideira, forno, liquidificador —, a intensidade do fogo, o tempo, se é para mexer e como saber que está pronto.' ],
		[ 'icon' => 'timer', 'tone' => 'sky', 'title' => 'Timers que avisam', 'text' => 'Som, vibração e aviso na tela. Vários timers ao mesmo tempo, mesmo trocando de passo.' ],
		[ 'icon' => 'mic', 'tone' => 'rose', 'title' => 'Mãos ocupadas?', 'text' => 'Diga “próximo”, “voltar”, “repetir” ou “timer”. O ChefMix também pode ler cada passo em voz alta.' ],
		[ 'icon' => 'rotate', 'tone' => 'lemon', 'title' => 'Pausou? Continue', 'text' => 'Saiu da receita no meio? Ao voltar, ela abre exatamente no passo em que você parou.' ],
	];
	?>
	<div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
		<?php foreach ( $features as $index => $feature ) :
			$tone = cmx_tones()[ $feature['tone'] ];
			?>
			<div class="glass-card rounded-4xl p-7 animate-fade-up" style="animation-delay: <?php echo (int) $index * 60; ?>ms">
				<span class="feature-orb" style="<?php echo esc_attr( cmx_tone_style( $tone ) ); ?>"><?php echo cmx_icon( $feature['icon'], 'h-6 w-6' ); // phpcs:ignore ?></span>
				<h3 class="mt-5 text-lg font-bold tracking-tight"><?php echo esc_html( $feature['title'] ); ?></h3>
				<p class="mt-2 leading-relaxed text-ink-soft"><?php echo esc_html( $feature['text'] ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<!-- Chefs -->
<?php if ( $chefs ) : ?>
	<section class="container mt-28">
		<div class="flex items-end justify-between gap-4">
			<div>
				<p class="eyebrow">Quem assina as receitas</p>
				<h2 class="section-title">Chefs</h2>
			</div>
			<a href="<?php echo esc_url( home_url( '/chefs/' ) ); ?>" class="btn btn-glass hidden sm:inline-flex">Todos os chefs</a>
		</div>
		<div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
			<?php
			foreach ( $chefs as $chef ) {
				get_template_part( 'template-parts/chef-card', null, [ 'user' => $chef ] );
			}
			?>
		</div>
	</section>
<?php endif; ?>

<!-- CTA final -->
<section class="container mt-28">
	<div class="glass relative overflow-hidden rounded-5xl px-8 py-14 text-center sm:px-16">
		<div class="absolute -left-20 -top-24 h-72 w-72 rounded-full bg-iris-200/60 blur-3xl" aria-hidden="true"></div>
		<div class="absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-mint-100/80 blur-3xl" aria-hidden="true"></div>
		<h2 class="relative text-3xl font-extrabold tracking-tight sm:text-5xl">Escolha uma receita.<br>O resto é com a gente.</h2>
		<p class="relative mx-auto mt-4 max-w-xl text-lg text-ink-soft">Salve suas favoritas e siga seus chefs para encontrar tudo num toque.</p>
		<div class="relative mt-8 flex flex-wrap justify-center gap-3">
			<a href="<?php echo esc_url( $archive ); ?>" class="btn btn-accent btn-lg">Ver receitas</a>
			<?php if ( ! is_user_logged_in() ) : ?>
				<button type="button" class="btn btn-glass btn-lg" data-login-open>Criar conta grátis</button>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
get_footer();
