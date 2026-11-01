<?php
/**
 * Receitas de uma cozinha (/cozinha/slug/).
 */
defined( 'ABSPATH' ) || exit;

get_header();
$term = get_queried_object();
get_template_part( 'template-parts/recipe-listing', null, [
	'title'    => $term->name,
	'subtitle' => $term->description,
	'current'  => $term->slug,
] );
get_footer();
