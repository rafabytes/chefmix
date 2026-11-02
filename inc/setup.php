<?php
/**
 * Configuração do tema, assets do Parcel e ajustes de consulta.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'chefmix', CHEFMIX_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'responsive-embeds' );

	add_image_size( 'cmx-card', 720, 540, true );
	add_image_size( 'cmx-hero', 1600, 1000, true );
	add_image_size( 'cmx-step', 960, 720, true );
} );

/* -------------------------------------------------------------------------
 * Assets compilados pelo Parcel (dist/main.js, dist/main.css)
 * ---------------------------------------------------------------------- */

function cmx_asset( string $file ): array {
	$path = CHEFMIX_DIR . '/dist/' . $file;
	return [
		'url'    => CHEFMIX_URI . '/dist/' . $file,
		'ver'    => file_exists( $path ) ? (string) filemtime( $path ) : CHEFMIX_VERSION,
		'exists' => file_exists( $path ),
	];
}

/** URLs que o Swup NÃO deve interceptar (precisam de carregamento completo). */
function cmx_swup_ignored_urls(): array {
	return array_values( array_unique( [ admin_url(), wp_login_url() ] ) );
}

add_action( 'wp_enqueue_scripts', function () {
	$css = cmx_asset( 'main.css' );
	$js  = cmx_asset( 'main.js' );

	if ( ! $js['exists'] ) {
		return; // admin_notices avisa para rodar o build
	}

	wp_enqueue_style( 'chefmix', $css['url'], [], $css['ver'] );
	wp_enqueue_script( 'chefmix', $js['url'], [], $js['ver'], [ 'in_footer' => true, 'strategy' => 'defer' ] );

	wp_add_inline_script(
		'chefmix',
		'window.CHEFMIX = ' . wp_json_encode( [
			'homeUrl'      => home_url( '/' ),
			'restUrl'      => esc_url_raw( rest_url( 'chefmix/v1/' ) ),
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'user'         => cmx_current_user_payload(),
			'logoutUrl'    => wp_logout_url( home_url( '/' ) ),
			'lostPassword' => wp_lostpassword_url(),
			'savedUrl'     => home_url( '/salvos/' ),
			'privacyUrl'   => get_privacy_policy_url(),
			'termsUrl'     => cmx_terms_url(),
			'google'       => (string) cmx_opt( 'google_client_id', '' ),
			'register'     => (bool) cmx_opt( 'cadastro_email', true ),
			'noSwup'       => cmx_swup_ignored_urls(),
		] ) . ';',
		'before'
	);
} );

// O WordPress 6.4+ injeta este CSS em todas as páginas; o tema não usa blocos no front.
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 100 );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! cmx_asset( 'main.js' )['exists'] ) {
		echo '<div class="notice notice-warning"><p><strong>ChefMix:</strong> os assets do tema ainda não foram compilados. Na pasta do tema, rode <code>npm install</code> e <code>npm run build</code>.</p></div>';
	}
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		echo '<div class="notice notice-error"><p><strong>ChefMix:</strong> ative o plugin <strong>Advanced Custom Fields PRO</strong> — os ingredientes e o passo a passo das receitas dependem dele.</p></div>';
	}
} );

/* -------------------------------------------------------------------------
 * <head> e limpeza
 * ---------------------------------------------------------------------- */

add_action( 'wp_head', function () {
	echo '<meta name="theme-color" content="#f4f5fa">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
	echo '<meta name="format-detection" content="telephone=no">' . "\n";
}, 1 );

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );

add_filter( 'body_class', function ( $classes ) {
	if ( is_singular( 'receita' ) ) {
		$classes[] = 'cmx-recipe';
	}
	return $classes;
} );

/* -------------------------------------------------------------------------
 * Consultas
 * ---------------------------------------------------------------------- */

add_action( 'pre_get_posts', function ( WP_Query $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_search() ) {
		$query->set( 'post_type', 'receita' );
	}
	if ( $query->is_post_type_archive( 'receita' ) || $query->is_tax( 'cozinha' ) || $query->is_search() ) {
		$query->set( 'posts_per_page', 18 );
	}
	// Perfil do chef: lista as receitas atribuídas a ele.
	if ( $query->is_author() ) {
		$query->set( 'post_type', 'receita' );
		$query->set( 'posts_per_page', 24 );
	}
} );

// A busca também considera os ingredientes e o nome das cozinhas.
add_filter( 'posts_search', function ( $search, WP_Query $query ) {
	global $wpdb;
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || '' === $search ) {
		return $search;
	}
	$like = '%' . $wpdb->esc_like( (string) $query->get( 's' ) ) . '%';
	return $wpdb->prepare(
		" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s
			OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = {$wpdb->posts}.ID AND pm.meta_key LIKE %s AND pm.meta_value LIKE %s)
			OR EXISTS (SELECT 1 FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'cozinha'
				INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
				WHERE tr.object_id = {$wpdb->posts}.ID AND t.name LIKE %s)) ",
		$like,
		$like,
		$wpdb->esc_like( 'ingredientes_' ) . '%_nome',
		$like,
		$like
	);
}, 10, 2 );
