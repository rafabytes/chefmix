<?php
/**
 * Página não encontrada.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="container pt-10 sm:pt-14">
	<?php
	get_template_part( 'template-parts/empty', null, [
		'icon'  => 'pot',
		'title' => 'Essa página saiu do forno',
		'text'  => 'O endereço mudou ou não existe. Que tal escolher uma receita?',
		'cta'   => [ 'label' => 'Ver receitas', 'url' => get_post_type_archive_link( 'receita' ) ],
	] );
	?>
</section>
<?php
get_footer();
