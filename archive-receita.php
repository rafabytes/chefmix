<?php
/**
 * Arquivo de receitas (/receitas/).
 */
defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/recipe-listing', null, [
	'title'    => 'Receitas',
	'subtitle' => 'Todas guiadas passo a passo: mise en place, fogo, tempo e o ponto certo em cada etapa.',
	'current'  => '',
] );
get_footer();
