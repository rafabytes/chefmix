<?php
/**
 * Listagem de receitas (arquivo, cozinha e busca).
 *
 * @var array $args { title: string, subtitle: string, current: string }
 */
defined( 'ABSPATH' ) || exit;

global $wp_query;
$found = (int) $wp_query->found_posts;
?>
<section class="container pt-10 sm:pt-14">
	<header class="max-w-3xl">
		<p class="eyebrow"><?php echo esc_html( sprintf( _n( '%d receita', '%d receitas', $found, 'chefmix' ), $found ) ); ?></p>
		<h1 class="page-title"><?php echo esc_html( $args['title'] ); ?></h1>
		<?php if ( ! empty( $args['subtitle'] ) ) : ?>
			<p class="mt-3 text-lg text-ink-soft"><?php echo esc_html( $args['subtitle'] ); ?></p>
		<?php endif; ?>
	</header>

	<div class="mt-8">
		<?php get_template_part( 'template-parts/cuisine-chips', null, [ 'current' => $args['current'] ?? '' ] ); ?>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="mt-10 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/recipe-card', null, [ 'post' => get_post() ] );
			endwhile;
			?>
		</div>
		<?php
		$links = paginate_links( [ 'type' => 'array', 'prev_text' => '‹', 'next_text' => '›' ] );
		if ( $links ) :
			?>
			<nav class="mt-14 flex flex-wrap justify-center gap-2" aria-label="Paginação">
				<?php foreach ( $links as $link ) : ?>
					<span class="pager"><?php echo wp_kses_post( $link ); ?></span>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<?php
		get_template_part( 'template-parts/empty', null, [
			'icon'  => 'search',
			'title' => 'Nada por aqui (ainda)',
			'text'  => 'Tente outra palavra ou explore todas as receitas.',
			'cta'   => [ 'label' => 'Ver todas as receitas', 'url' => get_post_type_archive_link( 'receita' ) ],
		] );
		?>
	<?php endif; ?>
</section>
