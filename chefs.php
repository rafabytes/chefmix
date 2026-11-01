<?php
/**
 * Lista de chefs (/chefs/) — rota registrada em inc/routes.php.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$chefs = get_users( [ 'role' => 'chef', 'orderby' => 'display_name' ] );
?>

<section class="container pt-10 sm:pt-14">
	<header class="max-w-2xl">
		<p class="eyebrow"><?php echo esc_html( sprintf( _n( '%d chef', '%d chefs', count( $chefs ), 'chefmix' ), count( $chefs ) ) ); ?></p>
		<h1 class="page-title">Chefs</h1>
		<p class="mt-3 text-lg text-ink-soft">Siga quem você gosta para encontrar as receitas deles em <strong class="font-semibold text-ink">Salvos</strong>.</p>
	</header>

	<?php if ( $chefs ) : ?>
		<div class="mt-10 grid grid-cols-2 gap-4 lg:grid-cols-4">
			<?php
			foreach ( $chefs as $chef ) {
				get_template_part( 'template-parts/chef-card', null, [ 'user' => $chef ] );
			}
			?>
		</div>
	<?php else : ?>
		<?php get_template_part( 'template-parts/empty', null, [ 'icon' => 'chef', 'title' => 'Em breve', 'text' => 'Os primeiros chefs estão chegando.' ] ); ?>
	<?php endif; ?>
</section>

<?php
get_footer();
