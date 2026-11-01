<?php
/**
 * Compatibilidade com o LiteSpeed Cache (todas as chamadas são do_action/add_filter: sem o plugin,
 * nada acontece).
 *
 * - O bundle do Parcel (dist/main.js, dist/main.css) e o window.CHEFMIX ficam fora de minificar, combinar,
 *   adiar/atrasar JS e do UCSS: o CSS já sai minificado e tem classes que só o React usa (o UCSS as
 *   removeria), e o JS precisa do window.CHEFMIX antes de rodar.
 * - Páginas de usuário logado só entram em cache privado (por usuário) — mesmo com ESI ligado, o cabeçalho
 *   e o window.CHEFMIX.user nunca vão para o cache público.
 * - Contadores de favoritos/seguidores marcam a página com tags; cada clique limpa só essas páginas.
 */
defined( 'ABSPATH' ) || exit;

/** Marca a página atual (cache do LiteSpeed) para ser limpa junto com a tag. */
function cmx_cache_tag( string $tag ): void {
	if ( ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		do_action( 'litespeed_tag_add', $tag );
	}
}

/** Limpa as páginas marcadas com a tag e o cache privado de quem clicou (window.CHEFMIX.user mudou). */
function cmx_cache_purge( string $tag ): void {
	do_action( 'litespeed_purge', $tag );
	do_action( 'litespeed_purge_private_all' );
}

/* -------------------------------------------------------------------------
 * Assets do tema fora da otimização do LiteSpeed
 * ---------------------------------------------------------------------- */

add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( 'chefmix' !== $handle ) {
		return $tag;
	}
	// Vale para o <script src> e para o inline "before" (window.CHEFMIX), que vêm juntos aqui.
	return preg_replace( '/<script(?=[\s>])(?![^>]*data-no-optimize)/', '<script data-no-optimize="1" data-no-defer="1"', $tag );
}, 10, 2 );

add_filter( 'style_loader_tag', function ( $tag, $handle ) {
	if ( 'chefmix' !== $handle ) {
		return $tag;
	}
	return preg_replace( '/<link(?=\s)(?![^>]*data-no-optimize)/', '<link data-no-optimize="1"', $tag );
}, 10, 2 );

/** Reforço pelas listas de exclusão (caso outro plugin reescreva as tags acima). */
function cmx_cache_exclude( $list, array $add ): array {
	$list = is_array( $list ) ? $list : array_filter( array_map( 'trim', explode( "\n", (string) $list ) ) );
	return array_values( array_unique( array_merge( $list, $add ) ) );
}

$cmx_cache_dist = '/themes/' . basename( CHEFMIX_DIR ) . '/dist/';

foreach ( [ 'litespeed_optimize_js_excludes', 'litespeed_optm_js_defer_exc', 'litespeed_optm_gm_js_exc' ] as $cmx_cache_filter ) {
	add_filter( $cmx_cache_filter, static fn( $list ) => cmx_cache_exclude( $list, [ $cmx_cache_dist . 'main.js', 'window.CHEFMIX' ] ) );
}
add_filter( 'litespeed_optimize_css_excludes', static fn( $list ) => cmx_cache_exclude( $list, [ $cmx_cache_dist . 'main.css' ] ) );

unset( $cmx_cache_dist, $cmx_cache_filter );

// Deploy novo (dist/main.js mudou): limpa todo o cache de páginas uma vez, para o HTML acompanhar os assets.
add_action( 'init', function () {
	$build = (string) cmx_asset( 'main.js' )['ver'];
	if ( get_option( 'chefmix_cache_build' ) !== $build ) {
		update_option( 'chefmix_cache_build', $build, true );
		do_action( 'litespeed_purge_all', 'chefmix: build novo do tema' );
	}
} );

/* -------------------------------------------------------------------------
 * O que nunca vai para o cache público
 * ---------------------------------------------------------------------- */

add_action( 'template_redirect', function () {
	if ( 'salvos' === cmx_route() ) {
		do_action( 'litespeed_control_set_nocache', 'chefmix: /salvos/ é pessoal' );
		return;
	}
	if ( is_user_logged_in() ) {
		do_action( 'litespeed_control_set_private', 'chefmix: cabeçalho e window.CHEFMIX.user são do usuário' );
	}
} );

// Rotas da REST do tema: só POST, mas se "Cache REST API" estiver ligado, nada daqui é guardado.
add_filter( 'rest_pre_dispatch', function ( $result, $server, WP_REST_Request $request ) {
	if ( str_starts_with( $request->get_route(), '/chefmix/v1/' ) ) {
		do_action( 'litespeed_control_set_nocache', 'chefmix: REST' );
	}
	return $result;
}, 10, 3 );
