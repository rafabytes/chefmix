<?php
/**
 * Ícone do site, favicon e metatags de busca/compartilhamento.
 *
 * - Ícone do site (Configurações → Geral): cadastrado sozinho a partir de assets/brand/icon-512.png na primeira
 *   visita de um administrador ao painel. O WordPress gera 32, 180 (Apple), 192 e 270 px e imprime os <link>.
 * - Favicon em SVG (assets/brand/favicon.svg) para as abas: nítido em qualquer densidade de tela.
 * - Descrição, Open Graph e Twitter Card. Se um plugin de SEO estiver ativo, ele cuida disso e o tema não imprime nada.
 *
 * Imagens geradas por tools/brand.php.
 */
defined( 'ABSPATH' ) || exit;

const CMX_SEO_TAGLINE = 'Receitas guiadas passo a passo, com panela, frigideira e forno de casa: mise en place, porções recalculadas e o ponto certo de cada etapa.';

function cmx_brand_url( string $file ): string {
	return CHEFMIX_URI . '/assets/brand/' . $file . '?ver=' . (int) @filemtime( CHEFMIX_DIR . '/assets/brand/' . $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
}

/* -------------------------------------------------------------------------
 * Ícone do site
 * ---------------------------------------------------------------------- */

/**
 * Envia o ícone da marca para a biblioteca de mídia (com os tamanhos de ícone do site) e o define como ícone do site.
 * Não faz nada se já houver um ícone ou se o tema já o cadastrou antes (o administrador pode trocá-lo ou removê-lo).
 *
 * @return int|WP_Error ID do anexo, 0 se nada foi feito.
 */
function cmx_register_site_icon() {
	if ( has_site_icon() || get_option( 'chefmix_site_icon' ) ) {
		return 0;
	}
	$source = CHEFMIX_DIR . '/assets/brand/icon-512.png';
	if ( ! is_readable( $source ) ) {
		return new WP_Error( 'cmx_icon_missing', 'assets/brand/icon-512.png não encontrado.' );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php';

	$tmp = wp_tempnam( 'chefmix-icon.png' );
	copy( $source, $tmp );

	// Igual ao recorte do Personalizar: só os tamanhos de ícone (32, 180, 192, 270), não os do tema.
	$site_icon = new WP_Site_Icon();
	add_filter( 'intermediate_image_sizes_advanced', [ $site_icon, 'additional_sizes' ] );
	$attachment_id = media_handle_sideload( [ 'name' => 'chefmix-icone.png', 'tmp_name' => $tmp ], 0, 'ChefMix — ícone do site' );
	remove_filter( 'intermediate_image_sizes_advanced', [ $site_icon, 'additional_sizes' ] );

	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $tmp );
		return $attachment_id;
	}

	update_post_meta( $attachment_id, '_wp_attachment_context', 'site-icon' ); // fica fora da biblioteca de mídia
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'ChefMix' );
	update_option( 'site_icon', $attachment_id );
	update_option( 'chefmix_site_icon', $attachment_id, false );
	return $attachment_id;
}

add_action( 'admin_init', function () {
	if ( current_user_can( 'manage_options' ) && ! wp_doing_ajax() ) {
		cmx_register_site_icon();
	}
} );

/* -------------------------------------------------------------------------
 * Favicon SVG (site, painel e tela de login)
 * ---------------------------------------------------------------------- */

$cmx_favicon = static function () {
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( cmx_brand_url( 'favicon.svg' ) ) );
	if ( ! has_site_icon() ) {
		printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( cmx_brand_url( 'icon-512.png' ) ) );
	}
};
add_action( 'wp_head', $cmx_favicon, 100 ); // depois do wp_site_icon (99)
add_action( 'admin_head', $cmx_favicon, 100 );
add_action( 'login_head', $cmx_favicon, 100 );

/* -------------------------------------------------------------------------
 * Descrição, Open Graph e Twitter Card
 * ---------------------------------------------------------------------- */

function cmx_seo_plugin_active(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || class_exists( 'The_SEO_Framework\Load' );
}

function cmx_seo_clip( string $text, int $length = 160 ): string {
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( $text ) ) ) );
	if ( mb_strlen( $text ) <= $length ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $length - 1 );
	return rtrim( mb_substr( $cut, 0, (int) mb_strrpos( $cut, ' ' ) ?: $length - 1 ), " ,.;:-" ) . '…';
}

/** @return array{description:string,type:string,url:string,image:array{url:string,width:int,height:int,alt:string}} */
function cmx_seo_data(): array {
	$site        = get_bloginfo( 'name' );
	$description = get_bloginfo( 'description' ) ?: CMX_SEO_TAGLINE;
	$type        = 'website';
	$image       = [ 'url' => cmx_brand_url( 'og-default.png' ), 'width' => 1200, 'height' => 630, 'alt' => $site . ' — receitas guiadas passo a passo' ];
	$url         = home_url( user_trailingslashit( $GLOBALS['wp']->request ?? '' ) );

	if ( is_singular() ) {
		$post = get_queried_object();
		$url  = (string) wp_get_canonical_url( $post );
		$type = 'article';

		if ( has_excerpt( $post ) ) {
			$description = get_the_excerpt( $post );
		} elseif ( 'receita' === $post->post_type ) {
			$minutes     = cmx_recipe_total_minutes( $post->ID );
			$description = sprintf(
				'%s: receita guiada passo a passo%s, com mise en place, porções ajustáveis e o ponto certo de cada etapa.',
				get_the_title( $post ),
				$minutes ? ' em ' . cmx_minutes( $minutes ) : ''
			);
		} elseif ( $post->post_content ) {
			$description = $post->post_content;
		}

		if ( has_post_thumbnail( $post ) ) {
			$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'cmx-hero' );
			if ( $thumb ) {
				$image = [ 'url' => $thumb[0], 'width' => (int) $thumb[1], 'height' => (int) $thumb[2], 'alt' => get_the_title( $post ) ];
			}
		}
	} elseif ( is_author() && ( $chef = get_queried_object() ) instanceof WP_User ) {
		$type        = 'profile';
		$description = get_the_author_meta( 'description', $chef->ID )
			?: sprintf( 'Receitas guiadas do chef %s no %s, passo a passo e com utensílios de casa.', $chef->display_name, $site );
	} elseif ( is_tax( 'cozinha' ) ) {
		$term        = get_queried_object();
		$description = term_description( $term ) ?: sprintf( 'Receitas da cozinha %s, guiadas passo a passo — com mise en place e o ponto certo de cada etapa.', $term->name );
	} elseif ( is_post_type_archive( 'receita' ) ) {
		$description = 'Todas as receitas guiadas do ' . $site . ': escolha, separe os ingredientes e siga um passo de cada vez.';
	} elseif ( 'chefs' === cmx_route() ) {
		$description = 'Conheça os chefs do ' . $site . ' e siga quem cozinha do jeito que você gosta.';
	}

	return [
		'description' => cmx_seo_clip( (string) $description ),
		'type'        => $type,
		'url'         => $url,
		'image'       => $image,
	];
}

add_action( 'wp_head', function () {
	if ( cmx_seo_plugin_active() || is_404() ) {
		return;
	}
	$data = cmx_seo_data();
	$meta = [
		[ 'name', 'description', $data['description'] ],
		[ 'property', 'og:locale', get_locale() ],
		[ 'property', 'og:site_name', get_bloginfo( 'name' ) ],
		[ 'property', 'og:type', $data['type'] ],
		[ 'property', 'og:title', wp_get_document_title() ],
		[ 'property', 'og:description', $data['description'] ],
		[ 'property', 'og:url', $data['url'] ],
		[ 'property', 'og:image', $data['image']['url'] ],
		[ 'property', 'og:image:width', (string) $data['image']['width'] ],
		[ 'property', 'og:image:height', (string) $data['image']['height'] ],
		[ 'property', 'og:image:alt', $data['image']['alt'] ],
		[ 'name', 'twitter:card', 'summary_large_image' ],
		[ 'name', 'twitter:title', wp_get_document_title() ],
		[ 'name', 'twitter:description', $data['description'] ],
		[ 'name', 'twitter:image', $data['image']['url'] ],
	];
	foreach ( $meta as [ $attr, $key, $value ] ) {
		printf( '<meta %s="%s" content="%s">' . "\n", $attr, esc_attr( $key ), esc_attr( $value ) );
	}
}, 5 );

// Páginas pessoais e de busca não entram nos resultados do Google.
add_filter( 'wp_robots', function ( array $robots ) {
	if ( 'salvos' === cmx_route() || is_search() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
} );
