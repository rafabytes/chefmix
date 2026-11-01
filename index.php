<?php
/**
 * Fallback genérico (posts do blog, arquivos não previstos).
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="container pt-10 sm:pt-14">
	<h1 class="page-title"><?php echo esc_html( is_archive() ? wp_strip_all_tags( get_the_archive_title() ) : get_bloginfo( 'name' ) ); ?></h1>
	<?php if ( have_posts() ) : ?>
		<div class="mt-10 grid gap-4">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<a href="<?php the_permalink(); ?>" class="glass-card block rounded-3xl p-6 hover:shadow-float">
					<h2 class="text-xl font-bold"><?php the_title(); ?></h2>
					<p class="mt-2 text-ink-soft"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></p>
				</a>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<?php get_template_part( 'template-parts/empty', null, [ 'title' => 'Nada por aqui', 'cta' => [ 'label' => 'Ver receitas', 'url' => get_post_type_archive_link( 'receita' ) ] ] ); ?>
	<?php endif; ?>
</section>
<?php
get_footer();
