<?php
/**
 * Receita: apresentação, pré-preparo (porções + ingredientes) e passo a passo.
 * O modo cozinhar (tela cheia) é aberto pela ilha React `Recipe`.
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$post_id = get_the_ID();
	$data    = cmx_recipe_payload( $post_id );
	$chef    = cmx_recipe_chef( $post_id );
	$cuisine = cmx_recipe_cuisine( $post_id );
	$favs    = cmx_favorites_count( $post_id );
	$credit  = cmx_photo_credit( $post_id );
	?>

	<article class="container pt-8 sm:pt-12">
		<!-- Cabeçalho -->
		<header class="grid items-center gap-8 lg:grid-cols-[1.1fr_.9fr] lg:gap-12">
			<div class="order-2 animate-fade-up lg:order-1">
				<div class="flex flex-wrap items-center gap-2">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'receita' ) ); ?>" class="pill-glass"><?php echo cmx_icon( 'arrow-left', 'h-4 w-4' ); // phpcs:ignore ?> Receitas</a>
					<?php if ( $cuisine ) : ?>
						<a href="<?php echo esc_url( get_term_link( $cuisine ) ); ?>" class="pill-glass"><?php echo esc_html( $cuisine->name ); ?></a>
					<?php endif; ?>
				</div>
				<h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="mt-4 max-w-xl text-lg leading-relaxed text-ink-soft"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<dl class="mt-7 grid max-w-xl grid-cols-2 gap-2.5 sm:grid-cols-4">
					<div class="stat"><dt>Tempo total</dt><dd><?php echo esc_html( cmx_minutes( $data['totalMin'] ) ?: '—' ); ?></dd></div>
					<div class="stat"><dt>Mão na massa</dt><dd><?php echo esc_html( cmx_minutes( $data['activeMin'] ) ?: '—' ); ?></dd></div>
					<div class="stat"><dt>Passos</dt><dd><?php echo esc_html( (string) count( $data['steps'] ) ); ?></dd></div>
					<div class="stat"><dt>Dificuldade</dt><dd><?php echo esc_html( $data['difficulty'] ); ?></dd></div>
				</dl>

				<div class="mt-7 flex flex-wrap items-center gap-3">
					<?php if ( $chef ) : ?>
						<a href="<?php echo esc_url( cmx_chef_url( $chef->ID ) ); ?>" class="flex items-center gap-3 rounded-full bg-white/60 py-1.5 pl-1.5 pr-4 ring-1 ring-ink/5 transition hover:bg-white">
							<?php echo cmx_chef_avatar( $chef->ID, 'h-9 w-9 text-xs' ); // phpcs:ignore ?>
							<span class="text-sm leading-tight"><span class="block text-ink-mute">Receita de</span><strong><?php echo esc_html( $chef->display_name ); ?></strong></span>
						</a>
						<?php cmx_island( 'FollowButton', [ 'id' => $chef->ID, 'count' => cmx_followers_count( $chef->ID ), 'size' => 'sm' ] ); ?>
					<?php endif; ?>
					<?php cmx_island( 'FavoriteButton', [ 'id' => $post_id, 'count' => $favs, 'variant' => 'pill' ] ); ?>
					<?php cmx_island( 'ShareButton', [ 'title' => get_the_title(), 'url' => get_permalink() ] ); ?>
				</div>
			</div>

			<div class="order-1 lg:order-2">
				<?php
				get_template_part( 'template-parts/cover', null, [
					'style'     => $data['style'],
					'image'     => $data['image'],
					'class'     => 'aspect-[5/4] rounded-5xl shadow-float sm:aspect-[4/3]',
					'orb_size'  => 'h-36 w-36 sm:h-44 sm:w-44',
					'icon_size' => 'h-16 w-16 sm:h-20 sm:w-20',
				] );
				?>
				<?php if ( $credit ) : ?>
					<p class="mt-3 px-3 text-right text-xs text-ink-mute">
						Foto: <a href="<?php echo esc_url( $credit['author_url'] ); ?>" class="underline-offset-2 hover:underline" rel="noopener" target="_blank"><?php echo esc_html( $credit['author'] ); ?></a>
						· <a href="<?php echo esc_url( $credit['page'] ); ?>" class="underline-offset-2 hover:underline" rel="noopener" target="_blank"><?php echo esc_html( $credit['site'] ); ?></a>
						· <a href="<?php echo esc_url( $credit['license_url'] ); ?>" class="underline-offset-2 hover:underline" rel="noopener license" target="_blank"><?php echo esc_html( $credit['license'] ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</header>

		<!-- Pré-preparo + passo a passo + modo cozinhar -->
		<?php
		ob_start();
		?>
		<div class="mt-14 grid gap-10 lg:grid-cols-[22rem_1fr]">
			<section>
				<h2 class="text-2xl font-bold tracking-tight">Mise en place</h2>
				<p class="mt-1 text-ink-mute"><?php echo esc_html( $data['servings'] . ' ' . $data['yieldLabel'] ); ?></p>
				<ul class="mt-5 space-y-2">
					<?php foreach ( $data['ingredients'] as $ing ) : ?>
						<li class="rounded-2xl bg-white/60 px-4 py-3"><strong><?php echo esc_html( cmx_fmt_amount( $ing['qty'], $ing['unit'] ) ); ?></strong> <?php echo esc_html( $ing['name'] ); ?><?php echo $ing['note'] ? ' <span class="text-ink-mute">(' . esc_html( $ing['note'] ) . ')</span>' : ''; ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
			<section>
				<h2 class="text-2xl font-bold tracking-tight">Passo a passo</h2>
				<ol class="mt-5 space-y-3">
					<?php foreach ( $data['steps'] as $i => $step ) : ?>
						<li class="glass-card rounded-3xl p-5">
							<p class="text-sm font-semibold text-ink-mute">Passo <?php echo (int) $i + 1; ?></p>
							<p class="mt-1 text-lg font-bold"><?php echo esc_html( $step['action'] ); ?></p>
							<p class="mt-1 text-ink-soft"><?php echo esc_html( ucfirst( cmx_fmt_settings( $step ) ) ); ?></p>
							<?php if ( $step['text'] ) : ?>
								<p class="mt-2 text-ink-soft"><?php echo esc_html( $step['text'] ); ?></p>
							<?php endif; ?>
							<?php if ( $step['done'] ) : ?>
								<p class="mt-2 text-ink-soft"><strong>Pronto quando</strong> <?php echo esc_html( $step['done'] ); ?>.</p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		</div>
		<?php
		$fallback = ob_get_clean();
		cmx_island( 'Recipe', $data, $fallback );
		?>

		<?php if ( trim( get_the_content() ) ) : ?>
			<section class="mx-auto mt-20 max-w-3xl">
				<h2 class="text-2xl font-bold tracking-tight">Sobre a receita</h2>
				<div class="prose-cmx mt-4"><?php the_content(); ?></div>
			</section>
		<?php endif; ?>
	</article>

	<?php
	// Mais receitas da mesma cozinha (ou recentes).
	$related = get_posts( [
		'post_type'    => 'receita',
		'numberposts'  => 3,
		'post__not_in' => [ $post_id ],
		'tax_query'    => $cuisine ? [ [ 'taxonomy' => 'cozinha', 'terms' => $cuisine->term_id ] ] : [], // phpcs:ignore
	] );
	if ( count( $related ) < 3 ) {
		$related = array_merge( $related, get_posts( [
			'post_type'    => 'receita',
			'numberposts'  => 3 - count( $related ),
			'post__not_in' => array_merge( [ $post_id ], wp_list_pluck( $related, 'ID' ) ),
		] ) );
	}
	if ( $related ) :
		?>
		<section class="container mt-24">
			<p class="eyebrow">Continue cozinhando</p>
			<h2 class="section-title">Você também pode gostar</h2>
			<div class="mt-8 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
				<?php
				foreach ( $related as $item ) {
					get_template_part( 'template-parts/recipe-card', null, [ 'post' => $item ] );
				}
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	// Dados estruturados (Google: rich results de receita).
	$schema = [
		'@context'           => 'https://schema.org',
		'@type'              => 'Recipe',
		'name'               => get_the_title(),
		'description'        => get_the_excerpt(),
		'datePublished'      => get_the_date( 'c' ),
		'author'             => [ '@type' => $chef ? 'Person' : 'Organization', 'name' => $chef ? $chef->display_name : 'ChefMix' ],
		'recipeYield'        => $data['servings'] . ' ' . $data['yieldLabel'],
		'totalTime'          => 'PT' . (int) $data['totalMin'] . 'M',
		'recipeCuisine'      => $data['cuisine'],
		'recipeIngredient'   => array_map( static fn( $i ) => trim( cmx_fmt_amount( $i['qty'], $i['unit'] ) . ' ' . $i['name'] ), $data['ingredients'] ),
		'recipeInstructions' => array_map( static fn( $s ) => [ '@type' => 'HowToStep', 'name' => $s['action'], 'text' => trim( ( $s['text'] ?: $s['action'] ) . ( $s['done'] ? ' Pronto quando ' . $s['done'] . '.' : '' ) ) ], $data['steps'] ),
	];
	if ( $data['image'] ) {
		$schema['image'] = [ $data['image'] ];
	}
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ); ?></script>

	<?php
endwhile;

get_footer();
