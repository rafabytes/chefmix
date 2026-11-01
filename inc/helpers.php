<?php
/**
 * Funções auxiliares: campos, opções, ícones, usuários e formatação.
 */
defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Campos e opções (ACF quando disponível, meta como fallback)
 * ---------------------------------------------------------------------- */

function cmx_field( string $key, $post_id = null, $default = '' ) {
	$post_id = $post_id ?: get_the_ID();
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $key, $post_id );
	} else {
		$value = get_post_meta( (int) $post_id, $key, true );
	}
	return ( null === $value || '' === $value || false === $value ) ? $default : $value;
}

/** Lê uma opção global (página "ChefMix → Configurações"). Constantes no wp-config.php têm prioridade. */
function cmx_opt( string $key, $default = '' ) {
	$const = 'CHEFMIX_' . strtoupper( $key );
	if ( defined( $const ) ) {
		return constant( $const );
	}
	$value = function_exists( 'get_field' ) ? get_field( $key, 'option' ) : get_option( 'options_' . $key, null );
	return ( null === $value || '' === $value ) ? $default : $value;
}

/* -------------------------------------------------------------------------
 * Ícones (mesma fonte do React: src/icons.json)
 * ---------------------------------------------------------------------- */

function cmx_icons(): array {
	static $icons = null;
	if ( null === $icons ) {
		$json  = file_get_contents( CHEFMIX_DIR . '/src/icons.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$icons = json_decode( (string) $json, true ) ?: [];
	}
	return $icons;
}

function cmx_icon( string $name, string $class = 'h-5 w-5', float $stroke = 1.8 ): string {
	$paths = cmx_icons()[ $name ] ?? '';
	if ( '' === $paths ) {
		return '';
	}
	return sprintf(
		'<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		esc_attr( $class ),
		esc_attr( (string) $stroke ),
		$paths // conteúdo estático do tema
	);
}

/* -------------------------------------------------------------------------
 * Ilhas React
 * ---------------------------------------------------------------------- */

/** Imprime o contêiner de uma ilha React com fallback em HTML (veja src/js/islands.jsx). */
function cmx_island( string $component, array $props = [], string $fallback = '', string $class = '' ): void {
	printf(
		'<div data-react="%s" data-props="%s"%s>%s</div>',
		esc_attr( $component ),
		esc_attr( wp_json_encode( $props ) ),
		$class ? ' class="' . esc_attr( $class ) . '"' : '',
		$fallback // phpcs:ignore WordPress.Security.EscapeOutput -- HTML montado pelo tema
	);
}

/* -------------------------------------------------------------------------
 * Usuários
 * ---------------------------------------------------------------------- */

function cmx_user( $user = null ): ?WP_User {
	if ( null === $user ) {
		$user = wp_get_current_user();
	} elseif ( is_numeric( $user ) ) {
		$user = get_userdata( (int) $user );
	}
	return ( $user instanceof WP_User && $user->exists() ) ? $user : null;
}

function cmx_is_chef( $user = null ): bool {
	$user = cmx_user( $user );
	return $user && in_array( 'chef', (array) $user->roles, true );
}

function cmx_is_admin( $user = null ): bool {
	$user = cmx_user( $user );
	return $user && user_can( $user, 'manage_options' );
}

/** Usuário comum (assinante): só favorita e segue chefs — sem perfil, sem foto, sem wp-admin. */
function cmx_is_member( $user = null ): bool {
	$user = cmx_user( $user );
	return $user && ! cmx_is_chef( $user ) && ! user_can( $user, 'edit_receitas' ) && ! user_can( $user, 'edit_posts' );
}

/** Foto do chef (campo "foto" do perfil). Assinantes nunca têm foto. */
function cmx_chef_photo( $user_id, string $size = 'thumbnail' ): string {
	$user = cmx_user( $user_id );
	if ( ! $user || cmx_is_member( $user ) ) {
		return '';
	}
	$photo = function_exists( 'get_field' ) ? get_field( 'foto', 'user_' . $user->ID ) : get_user_meta( $user->ID, 'foto', true );
	if ( is_array( $photo ) ) {
		return $photo['sizes'][ $size ] ?? $photo['url'] ?? '';
	}
	if ( is_numeric( $photo ) && $photo ) {
		return (string) wp_get_attachment_image_url( (int) $photo, $size );
	}
	return '';
}

function cmx_initials( string $name ): string {
	$parts = preg_split( '/\s+/', trim( $name ) ) ?: [];
	$first = $parts ? mb_substr( $parts[0], 0, 1 ) : '';
	$last  = count( $parts ) > 1 ? mb_substr( end( $parts ), 0, 1 ) : '';
	return mb_strtoupper( $first . $last );
}

/** Avatar do chef: foto, ou iniciais sobre um degradê suave. */
function cmx_chef_avatar( $user_id, string $class = 'h-10 w-10 text-sm' ): string {
	$user = cmx_user( $user_id );
	if ( ! $user ) {
		return '';
	}
	$photo = cmx_chef_photo( $user, 'thumbnail' );
	if ( $photo ) {
		return sprintf( '<img src="%s" alt="" class="%s shrink-0 rounded-full object-cover ring-2 ring-white" loading="lazy">', esc_url( $photo ), esc_attr( $class ) );
	}
	return sprintf(
		'<span class="%s inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-iris-200 to-mint-300 font-bold text-ink ring-2 ring-white" aria-hidden="true">%s</span>',
		esc_attr( $class ),
		esc_html( cmx_initials( $user->display_name ) )
	);
}

function cmx_chef_url( $user_id ): string {
	return get_author_posts_url( (int) $user_id );
}

/* -------------------------------------------------------------------------
 * Formatação
 * ---------------------------------------------------------------------- */

function cmx_minutes( $minutes ): string {
	$minutes = (int) round( (float) $minutes );
	if ( $minutes <= 0 ) {
		return '';
	}
	if ( $minutes < 60 ) {
		return $minutes . ' min';
	}
	$h = intdiv( $minutes, 60 );
	$m = $minutes % 60;
	return $m ? "{$h} h {$m} min" : "{$h} h";
}

function cmx_difficulties(): array {
	return [
		'facil'   => 'Fácil',
		'media'   => 'Média',
		'dificil' => 'Desafiadora',
	];
}

function cmx_difficulty_label( string $key ): string {
	return cmx_difficulties()[ $key ] ?? '';
}

/* -------------------------------------------------------------------------
 * Paleta das capas (cada cozinha tem um par de cores e um ícone)
 * ---------------------------------------------------------------------- */

function cmx_tones(): array {
	return [
		'iris'  => [ 'label' => 'Lavanda', 'c1' => '#d6cfff', 'c2' => '#bfe3ff' ],
		'mint'  => [ 'label' => 'Menta', 'c1' => '#bff3e2', 'c2' => '#fff1b8' ],
		'lemon' => [ 'label' => 'Limão', 'c1' => '#fff0b0', 'c2' => '#ffd2bf' ],
		'peach' => [ 'label' => 'Pêssego', 'c1' => '#ffd5c4', 'c2' => '#ffc3d1' ],
		'sky'   => [ 'label' => 'Céu', 'c1' => '#c4e4ff', 'c2' => '#c8f5e6' ],
		'rose'  => [ 'label' => 'Rosa', 'c1' => '#ffcbd6', 'c2' => '#ddd3ff' ],
	];
}

function cmx_cuisine_icons(): array {
	return [
		'pot'    => 'Panela',
		'leaf'   => 'Folha',
		'cactus' => 'Cacto',
		'pasta'  => 'Massa',
		'wine'   => 'Taça',
		'pepper' => 'Pimenta',
		'fish'   => 'Peixe',
		'soup'   => 'Sopa',
		'cake'   => 'Bolo',
	];
}

/** Padrões para as cozinhas cadastradas pelo seed (podem ser alterados no termo). */
function cmx_cuisine_defaults(): array {
	return [
		'brasileira'    => [ 'mint', 'leaf' ],
		'nordestina'    => [ 'lemon', 'cactus' ],
		'italiana'      => [ 'peach', 'pasta' ],
		'francesa'      => [ 'iris', 'wine' ],
		'mexicana'      => [ 'rose', 'pepper' ],
		'frutos-do-mar' => [ 'sky', 'fish' ],
		'sopas'         => [ 'peach', 'soup' ],
		'sobremesas'    => [ 'rose', 'cake' ],
	];
}

/** @return array{tone:string,icon:string,c1:string,c2:string} */
function cmx_cuisine_style( $term ): array {
	$defaults = $term ? ( cmx_cuisine_defaults()[ $term->slug ] ?? [ 'iris', 'pot' ] ) : [ 'iris', 'pot' ];
	$tone     = $term ? (string) get_term_meta( $term->term_id, 'tom', true ) : '';
	$icon     = $term ? (string) get_term_meta( $term->term_id, 'icone', true ) : '';
	$tone     = isset( cmx_tones()[ $tone ] ) ? $tone : $defaults[0];
	$icon     = isset( cmx_cuisine_icons()[ $icon ] ) ? $icon : $defaults[1];
	return [ 'tone' => $tone, 'icon' => $icon ] + cmx_tones()[ $tone ];
}

function cmx_tone_style( array $style ): string {
	return sprintf( '--c1:%s;--c2:%s', $style['c1'], $style['c2'] );
}
