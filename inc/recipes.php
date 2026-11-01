<?php
/**
 * Dados das receitas: normalização dos campos, formatação e payload do modo cozinhar.
 */
defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Formatação (o React tem as mesmas regras em src/js/lib/format.js)
 * ---------------------------------------------------------------------- */

function cmx_fmt_number( float $value ): string {
	$fractions = [ '0.25' => '¼', '0.33' => '⅓', '0.5' => '½', '0.67' => '⅔', '0.75' => '¾' ];
	$int       = floor( $value );
	$frac      = round( $value - $int, 2 );
	foreach ( $fractions as $dec => $glyph ) {
		if ( abs( $frac - (float) $dec ) < 0.04 ) {
			return ( $int > 0 ? (int) $int . ' ' : '' ) . $glyph;
		}
	}
	return rtrim( rtrim( number_format( $value, 1, ',', '.' ), '0' ), ',' );
}

function cmx_unit_plural( string $unit, float $qty ): string {
	if ( $qty <= 1 || in_array( $unit, [ 'g', 'kg', 'ml', 'l', 'a gosto' ], true ) ) {
		return str_replace( [ 'colher sopa', 'colher chá' ], [ 'colher (sopa)', 'colher (chá)' ], $unit );
	}
	$plural = [
		'xícara'      => 'xícaras',
		'colher sopa' => 'colheres (sopa)',
		'colher chá'  => 'colheres (chá)',
		'unidade'     => 'unidades',
		'dente'       => 'dentes',
		'fatia'       => 'fatias',
		'folha'       => 'folhas',
		'ramo'        => 'ramos',
		'maço'        => 'maços',
		'lata'        => 'latas',
		'pitada'      => 'pitadas',
	];
	return $plural[ $unit ] ?? $unit;
}

/** "200 g", "1 ½ xícara", "2 dentes", "a gosto". */
function cmx_fmt_amount( $qty, string $unit ): string {
	if ( 'a gosto' === $unit || '' === (string) $qty || null === $qty ) {
		return 'a gosto' === $unit ? 'a gosto' : '';
	}
	$qty  = (float) $qty;
	$unit = $unit ? cmx_unit_plural( $unit, $qty ) : '';
	if ( 'unidade' === $unit || 'unidades' === $unit ) {
		$unit = '';
	}
	return trim( cmx_fmt_number( $qty ) . ' ' . $unit );
}

function cmx_fmt_duration( int $seconds ): string {
	if ( $seconds <= 0 ) {
		return '';
	}
	$m = intdiv( $seconds, 60 );
	$s = $seconds % 60;
	if ( $m && $s ) {
		return "{$m} min {$s} s";
	}
	return $m ? "{$m} min" : "{$s} s";
}

/** Resumo dos ajustes de um passo: "3 min · fogo médio · mexa sem parar". */
function cmx_fmt_settings( array $step ): string {
	return implode( ' · ', array_filter( [
		cmx_fmt_duration( $step['seconds'] ),
		$step['flame'] ? 'fogo ' . mb_strtolower( cmx_flames()[ $step['flame'] ] ?? '' ) : '',
		$step['temp'] ? 'forno a ' . $step['temp'] . ' °C' : '',
		$step['speed'] ? 'velocidade ' . mb_strtolower( cmx_speeds()[ $step['speed'] ] ?? '' ) : '',
		$step['stir'] ? 'mexer: ' . mb_strtolower( cmx_stirring()[ $step['stir'] ] ?? '' ) : '',
		$step['lid'] ? mb_strtolower( cmx_lids()[ $step['lid'] ] ?? '' ) : '',
	] ) );
}

/* -------------------------------------------------------------------------
 * Campos normalizados
 * ---------------------------------------------------------------------- */

function cmx_rows( string $field, int $post_id ): array {
	$rows = function_exists( 'get_field' ) ? get_field( $field, $post_id ) : [];
	return is_array( $rows ) ? $rows : [];
}

function cmx_recipe_ingredients( int $post_id ): array {
	$list = [];
	foreach ( cmx_rows( 'ingredientes', $post_id ) as $row ) {
		if ( empty( $row['nome'] ) ) {
			continue;
		}
		$list[] = [
			'group' => trim( (string) ( $row['grupo'] ?? '' ) ),
			'qty'   => ( '' === ( $row['quantidade'] ?? '' ) || null === $row['quantidade'] ) ? null : (float) $row['quantidade'],
			'unit'  => (string) ( $row['unidade'] ?? '' ),
			'name'  => trim( (string) $row['nome'] ),
			'note'  => trim( (string) ( $row['obs'] ?? '' ) ),
		];
	}
	return $list;
}

function cmx_recipe_steps( int $post_id ): array {
	$steps = [];
	foreach ( cmx_rows( 'passos', $post_id ) as $row ) {
		$place   = (string) ( $row['local'] ?? 'bancada' );
		$place   = isset( cmx_places()[ $place ] ) ? $place : 'bancada';
		$heat    = in_array( $place, cmx_places_heat(), true );
		$motor   = in_array( $place, cmx_places_motor(), true );
		$seconds = (int) ( $row['tempo_min'] ?? 0 ) * 60 + (int) ( $row['tempo_seg'] ?? 0 );
		$image   = ! empty( $row['imagem'] ) ? (int) $row['imagem'] : 0;

		$ingredients = [];
		foreach ( (array) ( $row['ingredientes'] ?? [] ) as $ing ) {
			if ( empty( $ing['nome'] ) ) {
				continue;
			}
			$ingredients[] = [
				'qty'  => ( '' === ( $ing['quantidade'] ?? '' ) || null === $ing['quantidade'] ) ? null : (float) $ing['quantidade'],
				'unit' => (string) ( $ing['unidade'] ?? '' ),
				'name' => trim( (string) $ing['nome'] ),
			];
		}

		$steps[] = [
			'place'       => $place,
			'action'      => trim( (string) ( $row['acao'] ?? '' ) ),
			'text'        => trim( (string) ( $row['instrucao'] ?? '' ) ),
			'ingredients' => $ingredients,
			'seconds'     => $seconds,
			'flame'       => $heat ? (string) ( $row['fogo'] ?? '' ) : '',
			'stir'        => $heat ? (string) ( $row['mexer'] ?? '' ) : '',
			'lid'         => $heat ? (string) ( $row['tampa'] ?? '' ) : '',
			'temp'        => 'forno' === $place ? (string) ( $row['temperatura'] ?? '' ) : '',
			'speed'       => $motor ? (string) ( $row['velocidade'] ?? '' ) : '',
			'done'        => trim( (string) ( $row['pronto'] ?? '' ) ),
			'tip'         => trim( (string) ( $row['dica'] ?? '' ) ),
			'warning'     => trim( (string) ( $row['atencao'] ?? '' ) ),
			'image'       => $image ? [
				'url' => (string) wp_get_attachment_image_url( $image, 'cmx-step' ),
				'alt' => (string) get_post_meta( $image, '_wp_attachment_image_alt', true ),
			] : null,
		];
	}
	return $steps;
}

/** Tempo total em minutos: campo "tempo total" ou soma dos passos (passos sem tempo contam 1 min). */
function cmx_recipe_total_minutes( int $post_id, ?array $steps = null ): int {
	$total = (int) cmx_field( 'tempo_total', $post_id, 0 );
	if ( $total > 0 ) {
		return $total;
	}
	$steps   = $steps ?? cmx_recipe_steps( $post_id );
	$seconds = 0;
	foreach ( $steps as $step ) {
		$seconds += $step['seconds'] ?: 60;
	}
	return (int) ceil( $seconds / 60 );
}

function cmx_recipe_cuisine( int $post_id ): ?WP_Term {
	$terms = get_the_terms( $post_id, 'cozinha' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/** Chef responsável (autor com papel "chef"); null = receita da equipe ChefMix. */
function cmx_recipe_chef( $post ): ?WP_User {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$author = get_userdata( (int) $post->post_author );
	return ( $author && cmx_is_chef( $author ) ) ? $author : null;
}

function cmx_chef_payload( ?WP_User $chef ): ?array {
	if ( ! $chef ) {
		return null;
	}
	return [
		'id'       => $chef->ID,
		'name'     => $chef->display_name,
		'url'      => cmx_chef_url( $chef->ID ),
		'photo'    => cmx_chef_photo( $chef, 'thumbnail' ),
		'initials' => cmx_initials( $chef->display_name ),
	];
}

/** Dados resumidos para cards e listagens. */
function cmx_recipe_card( $post ): array {
	$post    = get_post( $post );
	$steps   = cmx_recipe_steps( $post->ID );
	$cuisine = cmx_recipe_cuisine( $post->ID );
	return [
		'id'         => $post->ID,
		'title'      => get_the_title( $post ),
		'url'        => get_permalink( $post ),
		'excerpt'    => has_excerpt( $post ) ? get_the_excerpt( $post ) : '',
		'minutes'    => cmx_recipe_total_minutes( $post->ID, $steps ),
		'steps'      => count( $steps ),
		'difficulty' => (string) cmx_field( 'dificuldade', $post->ID, 'facil' ),
		'cuisine'    => $cuisine,
		'style'      => cmx_cuisine_style( $cuisine ),
		'image'      => has_post_thumbnail( $post ) ? (string) get_the_post_thumbnail_url( $post, 'cmx-card' ) : '',
		'chef'       => cmx_recipe_chef( $post ),
		'favorites'  => cmx_favorites_count( $post->ID ),
	];
}

/** Payload completo para a ilha React `Recipe` (pré-preparo + modo cozinhar). */
function cmx_recipe_payload( $post ): array {
	$post    = get_post( $post );
	$steps   = cmx_recipe_steps( $post->ID );
	$cuisine = cmx_recipe_cuisine( $post->ID );
	$lines   = preg_split( '/\r\n|\r|\n/', (string) cmx_field( 'utensilios', $post->ID, '' ) ) ?: [];

	return [
		'id'          => $post->ID,
		'title'       => get_the_title( $post ),
		'url'         => get_permalink( $post ),
		'servings'    => max( 1, (int) cmx_field( 'porcoes', $post->ID, 4 ) ),
		'yieldLabel'  => (string) cmx_field( 'rendimento_unidade', $post->ID, 'porções' ),
		'difficulty'  => cmx_difficulty_label( (string) cmx_field( 'dificuldade', $post->ID, 'facil' ) ),
		'activeMin'   => (int) cmx_field( 'tempo_preparo', $post->ID, 0 ),
		'totalMin'    => cmx_recipe_total_minutes( $post->ID, $steps ),
		'utensils'    => array_values( array_filter( array_map( 'trim', $lines ) ) ),
		'ingredients' => cmx_recipe_ingredients( $post->ID ),
		'steps'       => $steps,
		'chef'        => cmx_chef_payload( cmx_recipe_chef( $post ) ),
		'cuisine'     => $cuisine ? $cuisine->name : '',
		'style'       => cmx_cuisine_style( $cuisine ),
		'image'       => has_post_thumbnail( $post ) ? (string) get_the_post_thumbnail_url( $post, 'cmx-hero' ) : '',
	];
}
