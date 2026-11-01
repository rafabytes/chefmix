<?php
/**
 * Papéis e permissões.
 *
 * | Papel          | O que pode                                                                    |
 * |----------------|-------------------------------------------------------------------------------|
 * | Administrador  | Tudo: cria receitas, cadastra chefs, atribui receitas, publica.               |
 * | Chef (`chef`)  | Edita só as receitas atribuídas a ele (autor) e o próprio perfil público.     |
 * | Assinante      | Favorita receitas e segue chefs. Sem wp-admin, sem perfil público, sem foto.  |
 */
defined( 'ABSPATH' ) || exit;

const CMX_ROLES_VERSION = '1';

function cmx_recipe_caps_all(): array {
	return [
		'create_receitas',
		'edit_receitas',
		'edit_others_receitas',
		'edit_published_receitas',
		'edit_private_receitas',
		'publish_receitas',
		'read_private_receitas',
		'delete_receitas',
		'delete_others_receitas',
		'delete_published_receitas',
		'delete_private_receitas',
	];
}

function cmx_install_roles(): void {
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( cmx_recipe_caps_all() as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	remove_role( 'chef' );
	add_role( 'chef', 'Chef', [
		'read'                    => true,
		'upload_files'            => true,
		'edit_receitas'           => true,
		'edit_published_receitas' => true,
	] );

	update_option( 'chefmix_roles_version', CMX_ROLES_VERSION );
}

add_action( 'init', function () {
	if ( get_option( 'chefmix_roles_version' ) !== CMX_ROLES_VERSION ) {
		cmx_install_roles();
	}
}, 1 );

add_action( 'after_switch_theme', 'cmx_install_roles' );

/* -------------------------------------------------------------------------
 * Assinantes: fora do wp-admin e sem barra de administração
 * ---------------------------------------------------------------------- */

add_action( 'admin_init', function () {
	if ( wp_doing_ajax() || ! is_user_logged_in() || ! cmx_is_member() ) {
		return;
	}
	if ( isset( $GLOBALS['pagenow'] ) && 'admin-post.php' === $GLOBALS['pagenow'] ) {
		return;
	}
	wp_safe_redirect( home_url( '/salvos/' ) );
	exit;
} );

add_filter( 'show_admin_bar', function ( $show ) {
	return cmx_is_member() ? false : $show;
} );

add_filter( 'login_redirect', function ( $redirect, $requested, $user ) {
	if ( ! $user instanceof WP_User ) {
		return $redirect;
	}
	if ( cmx_is_member( $user ) ) {
		return ( $requested && false === strpos( $requested, '/wp-admin' ) ) ? $requested : home_url( '/' );
	}
	if ( cmx_is_chef( $user ) && ( ! $requested || admin_url() === $requested ) ) {
		return admin_url( 'edit.php?post_type=receita' );
	}
	return $redirect;
}, 10, 3 );

/* -------------------------------------------------------------------------
 * Avatares: chefs usam a foto do perfil; assinantes não têm foto
 * ---------------------------------------------------------------------- */

add_filter( 'pre_get_avatar_data', function ( $args, $id_or_email ) {
	$user = null;
	if ( is_numeric( $id_or_email ) ) {
		$user = get_userdata( (int) $id_or_email );
	} elseif ( $id_or_email instanceof WP_User ) {
		$user = $id_or_email;
	} elseif ( $id_or_email instanceof WP_Post ) {
		$user = get_userdata( (int) $id_or_email->post_author );
	} elseif ( $id_or_email instanceof WP_Comment && $id_or_email->user_id ) {
		$user = get_userdata( (int) $id_or_email->user_id );
	} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
	}
	if ( ! $user ) {
		return $args;
	}
	if ( cmx_is_member( $user ) ) {
		$args['url'] = ''; // get_avatar() devolve false: nada de foto para assinantes
		return $args;
	}
	$photo = cmx_chef_photo( $user, 'thumbnail' );
	if ( $photo ) {
		$args['url']          = $photo;
		$args['found_avatar'] = true;
	}
	return $args;
}, 10, 2 );

/* -------------------------------------------------------------------------
 * Perfil público só para chefs (/chef/slug/)
 * ---------------------------------------------------------------------- */

add_action( 'template_redirect', function () {
	if ( ! is_author() ) {
		return;
	}
	$author = get_queried_object();
	if ( ! $author instanceof WP_User || ! cmx_is_chef( $author ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
} );

/* -------------------------------------------------------------------------
 * wp-admin enxuto para o chef
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	if ( ! cmx_is_chef() ) {
		return;
	}
	foreach ( [ 'index.php', 'edit.php', 'edit-comments.php', 'tools.php' ] as $page ) {
		remove_menu_page( $page );
	}
	// O chef não tem `edit_posts`, então o WP marca "edit.php" (Posts) como proibido antes deste hook.
	// Como o menu Receitas fica com um só submenu acessível (que o WP descarta), edit.php?post_type=receita
	// fica sem "pai" e herdaria essa proibição. Os Posts não existem para o chef: limpamos a marcação.
	global $submenu, $_wp_submenu_nopriv;
	unset( $submenu['edit.php'], $_wp_submenu_nopriv['edit.php'] );
}, 999 );

add_action( 'load-index.php', function () {
	if ( cmx_is_chef() ) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=receita' ) );
		exit;
	}
} );

add_action( 'admin_bar_menu', function ( WP_Admin_Bar $bar ) {
	if ( ! cmx_is_chef() ) {
		return;
	}
	foreach ( [ 'wp-logo', 'new-content', 'comments', 'updates' ] as $node ) {
		$bar->remove_node( $node );
	}
	$bar->add_node( [
		'id'     => 'cmx-chef-profile',
		'parent' => 'my-account',
		'title'  => 'Ver meu perfil público',
		'href'   => cmx_chef_url( get_current_user_id() ),
	] );
}, 999 );

// O chef só enxerga as próprias receitas e mídias.
add_action( 'pre_get_posts', function ( WP_Query $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || current_user_can( 'edit_others_receitas' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && in_array( $screen->id, [ 'edit-receita', 'upload' ], true ) ) {
		$query->set( 'author', get_current_user_id() );
	}
} );

add_filter( 'ajax_query_attachments_args', function ( $args ) {
	if ( ! current_user_can( 'edit_others_receitas' ) ) {
		$args['author'] = get_current_user_id();
	}
	return $args;
} );

add_filter( 'views_edit-receita', function ( $views ) {
	return current_user_can( 'edit_others_receitas' ) ? $views : [];
} );
