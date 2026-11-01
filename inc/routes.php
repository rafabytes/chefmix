<?php
/**
 * Rotas próprias do tema (sem páginas no banco):
 *   /chefs/   → lista de chefs (chefs.php)
 *   /salvos/  → receitas favoritas e chefs seguidos do usuário logado (salvos.php)
 */
defined( 'ABSPATH' ) || exit;

function cmx_routes(): array {
	return [
		'chefs'  => [ 'template' => 'chefs.php', 'title' => 'Chefs' ],
		'salvos' => [ 'template' => 'salvos.php', 'title' => 'Salvos' ],
	];
}

add_action( 'init', function () {
	foreach ( array_keys( cmx_routes() ) as $route ) {
		add_rewrite_rule( '^' . $route . '/?$', 'index.php?cmx_route=' . $route, 'top' );
	}
	add_rewrite_tag( '%cmx_route%', '(' . implode( '|', array_keys( cmx_routes() ) ) . ')' );
} );

// Regrava as regras de URL quando o tema muda de versão (rotas novas, base /chef/ etc.).
add_action( 'init', function () {
	if ( get_option( 'chefmix_rewrite_version' ) !== CHEFMIX_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'chefmix_rewrite_version', CHEFMIX_VERSION );
	}
}, 99 );

add_action( 'after_switch_theme', function () {
	delete_option( 'chefmix_rewrite_version' );
} );

function cmx_route(): string {
	return (string) get_query_var( 'cmx_route' );
}

add_filter( 'template_include', function ( $template ) {
	$route = cmx_route();
	if ( $route && isset( cmx_routes()[ $route ] ) ) {
		global $wp_query;
		$wp_query->is_home     = false;
		$wp_query->is_404      = false;
		$wp_query->is_archive  = false;
		$wp_query->is_singular = false;
		status_header( 200 );
		return locate_template( cmx_routes()[ $route ]['template'] ) ?: $template;
	}
	return $template;
} );

add_filter( 'document_title_parts', function ( $parts ) {
	$route = cmx_route();
	if ( $route && isset( cmx_routes()[ $route ] ) ) {
		$parts['title'] = cmx_routes()[ $route ]['title'];
	}
	if ( is_author() && ( $author = get_queried_object() ) instanceof WP_User ) {
		$parts['title'] = 'Chef ' . $author->display_name;
	}
	return $parts;
} );

// /salvos/ é pessoal: nunca deve ir para cache de página.
add_action( 'template_redirect', function () {
	if ( 'salvos' === cmx_route() ) {
		nocache_headers();
	}
} );
