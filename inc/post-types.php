<?php
/**
 * Tipos de conteúdo: Receita (`receita`) e Cozinha (`cozinha`).
 *
 * Permissões próprias (capability_type receita): só o administrador cria receitas
 * (`create_receitas`); o chef edita apenas as receitas atribuídas a ele (post_author).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'receita', [
		'labels'          => [
			'name'               => 'Receitas',
			'singular_name'      => 'Receita',
			'menu_name'          => 'Receitas',
			'add_new'            => 'Nova receita',
			'add_new_item'       => 'Nova receita',
			'edit_item'          => 'Editar receita',
			'new_item'           => 'Nova receita',
			'view_item'          => 'Ver receita',
			'view_items'         => 'Ver receitas',
			'search_items'       => 'Buscar receitas',
			'not_found'          => 'Nenhuma receita encontrada.',
			'not_found_in_trash' => 'Nenhuma receita na lixeira.',
			'all_items'          => 'Todas as receitas',
			'featured_image'     => 'Foto da receita',
			'set_featured_image' => 'Definir foto da receita',
		],
		'public'          => true,
		'show_in_rest'    => false,
		'menu_icon'       => 'dashicons-carrot',
		'menu_position'   => 5,
		'has_archive'     => 'receitas',
		'rewrite'         => [ 'slug' => 'receitas', 'with_front' => false ],
		'supports'        => [ 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ],
		'capability_type' => [ 'receita', 'receitas' ],
		'map_meta_cap'    => true,
		'capabilities'    => [ 'create_posts' => 'create_receitas' ],
	] );

	register_taxonomy( 'cozinha', [ 'receita' ], [
		'labels'            => [
			'name'          => 'Cozinhas',
			'singular_name' => 'Cozinha',
			'menu_name'     => 'Cozinhas',
			'all_items'     => 'Todas as cozinhas',
			'edit_item'     => 'Editar cozinha',
			'add_new_item'  => 'Nova cozinha',
			'search_items'  => 'Buscar cozinhas',
			'not_found'     => 'Nenhuma cozinha encontrada.',
		],
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => false,
		'rewrite'           => [ 'slug' => 'cozinha', 'with_front' => false ],
		'capabilities'      => [
			'manage_terms' => 'manage_options',
			'edit_terms'   => 'manage_options',
			'delete_terms' => 'manage_options',
			'assign_terms' => 'edit_receitas',
		],
	] );
} );

// Perfil público do chef: /chef/nome-do-chef/
add_action( 'init', function () {
	global $wp_rewrite;
	$wp_rewrite->author_base = 'chef';
} );

// Receitas usam o editor clássico + campos do ACF (formulário enxuto, um passo por linha).
add_filter( 'use_block_editor_for_post_type', function ( $use, $post_type ) {
	return 'receita' === $post_type ? false : $use;
}, 10, 2 );

add_filter( 'enter_title_here', function ( $text, $post ) {
	return 'receita' === $post->post_type ? 'Nome da receita (ex.: Risoto de cogumelos)' : $text;
}, 10, 2 );

// O "resumo" vira a frase de apresentação do card.
add_action( 'add_meta_boxes_receita', function () {
	remove_meta_box( 'postexcerpt', 'receita', 'normal' );
	add_meta_box( 'postexcerpt', 'Frase de apresentação (aparece nos cards)', 'post_excerpt_meta_box', 'receita', 'normal', 'high' );
} );
