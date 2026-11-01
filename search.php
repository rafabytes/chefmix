<?php
/**
 * Resultados da busca (somente receitas — veja inc/setup.php).
 */
defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/recipe-listing', null, [
	'title'    => sprintf( 'Busca: “%s”', get_search_query() ),
	'subtitle' => '',
	'current'  => '',
] );
get_footer();
