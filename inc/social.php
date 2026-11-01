<?php
/**
 * Favoritos e "seguir chef".
 *
 *   user meta  cmx_favorites  → IDs das receitas favoritas
 *   user meta  cmx_following  → IDs dos chefs seguidos
 *   post meta  cmx_favorites  → contador de favoritos da receita
 *   user meta  cmx_followers  → contador de seguidores do chef
 *
 * REST (cookie + nonce):
 *   POST   /chefmix/v1/favorites/{id}  {on:bool}
 *   POST   /chefmix/v1/follow/{id}     {on:bool}
 *   POST   /chefmix/v1/me              {confirm:true}   (exclui a conta; apenas assinantes)
 *
 * Os contadores aparecem em páginas guardadas no cache do LiteSpeed: quem imprime um contador marca a
 * página com uma tag e cada clique limpa só as páginas daquela receita/chef (veja inc/cache.php).
 */
defined( 'ABSPATH' ) || exit;

function cmx_id_list( int $user_id, string $key ): array {
	$list = get_user_meta( $user_id, $key, true );
	return is_array( $list ) ? array_values( array_unique( array_map( 'intval', $list ) ) ) : [];
}

function cmx_user_favorites( int $user_id ): array {
	return cmx_id_list( $user_id, 'cmx_favorites' );
}

function cmx_user_following( int $user_id ): array {
	return cmx_id_list( $user_id, 'cmx_following' );
}

function cmx_followers_count( int $chef_id ): int {
	cmx_cache_tag( 'cmx_chef_' . $chef_id );
	return (int) get_user_meta( $chef_id, 'cmx_followers', true );
}

function cmx_favorites_count( int $post_id ): int {
	cmx_cache_tag( 'cmx_r_' . $post_id );
	return (int) get_post_meta( $post_id, 'cmx_favorites', true );
}

/** Dados do usuário logado para o front-end (window.CHEFMIX.user). */
function cmx_current_user_payload(): ?array {
	$user = cmx_user();
	if ( ! $user ) {
		return null;
	}
	$role = cmx_is_chef( $user ) ? 'chef' : ( user_can( $user, 'edit_receitas' ) ? 'admin' : 'member' );
	return [
		'id'        => $user->ID,
		'name'      => $user->display_name,
		'firstName' => $user->first_name ?: strtok( $user->display_name, ' ' ),
		'email'     => $user->user_email,
		'role'      => $role,
		'favorites' => cmx_user_favorites( $user->ID ),
		'following' => cmx_user_following( $user->ID ),
		'profile'   => 'chef' === $role ? cmx_chef_url( $user->ID ) : '',
		'panel'     => 'member' === $role ? '' : admin_url( 'edit.php?post_type=receita' ),
		'photo'     => 'chef' === $role ? cmx_chef_photo( $user ) : '',
	];
}

/** Liga/desliga um ID numa lista do usuário e ajusta o contador do alvo. Retorna o estado final. */
function cmx_toggle( int $user_id, string $list_key, int $target_id, bool $on, callable $counter ): bool {
	$list = cmx_id_list( $user_id, $list_key );
	$has  = in_array( $target_id, $list, true );

	if ( $on && ! $has ) {
		$list[] = $target_id;
		$counter( 1 );
	} elseif ( ! $on && $has ) {
		$list = array_values( array_diff( $list, [ $target_id ] ) );
		$counter( -1 );
	}
	update_user_meta( $user_id, $list_key, $list );
	return $on;
}

add_action( 'rest_api_init', function () {
	$logged = static fn() => is_user_logged_in();

	register_rest_route( 'chefmix/v1', '/favorites/(?P<id>\d+)', [
		'methods'             => 'POST',
		'permission_callback' => $logged,
		'callback'            => function ( WP_REST_Request $request ) {
			$post_id = (int) $request['id'];
			$post    = get_post( $post_id );
			if ( ! $post || 'receita' !== $post->post_type || 'publish' !== $post->post_status ) {
				return new WP_Error( 'cmx_not_found', 'Receita não encontrada.', [ 'status' => 404 ] );
			}
			$on = (bool) $request->get_param( 'on' );
			cmx_toggle( get_current_user_id(), 'cmx_favorites', $post_id, $on, function ( int $delta ) use ( $post_id ) {
				$count = max( 0, (int) get_post_meta( $post_id, 'cmx_favorites', true ) + $delta );
				update_post_meta( $post_id, 'cmx_favorites', $count );
			} );
			cmx_cache_purge( 'cmx_r_' . $post_id );
			return [
				'on'    => $on,
				'count' => (int) get_post_meta( $post_id, 'cmx_favorites', true ),
				'list'  => cmx_user_favorites( get_current_user_id() ),
			];
		},
	] );

	register_rest_route( 'chefmix/v1', '/follow/(?P<id>\d+)', [
		'methods'             => 'POST',
		'permission_callback' => $logged,
		'callback'            => function ( WP_REST_Request $request ) {
			$chef_id = (int) $request['id'];
			if ( ! cmx_is_chef( $chef_id ) ) {
				return new WP_Error( 'cmx_not_found', 'Chef não encontrado.', [ 'status' => 404 ] );
			}
			if ( get_current_user_id() === $chef_id ) {
				return new WP_Error( 'cmx_self', 'Você não pode seguir a si mesmo.', [ 'status' => 400 ] );
			}
			$on = (bool) $request->get_param( 'on' );
			cmx_toggle( get_current_user_id(), 'cmx_following', $chef_id, $on, function ( int $delta ) use ( $chef_id ) {
				update_user_meta( $chef_id, 'cmx_followers', max( 0, cmx_followers_count( $chef_id ) + $delta ) );
			} );
			cmx_cache_purge( 'cmx_chef_' . $chef_id );
			return [
				'on'    => $on,
				'count' => cmx_followers_count( $chef_id ),
				'list'  => cmx_user_following( get_current_user_id() ),
			];
		},
	] );

	// Exclusão da própria conta (LGPD) — só para assinantes; chefs e administradores falam com o admin.
	// POST (DELETE ainda aceito): o LiteSpeed só troca o cookie de sessão do cache (vary) em GET/POST.
	register_rest_route( 'chefmix/v1', '/me', [
		'methods'             => [ 'POST', 'DELETE' ],
		'permission_callback' => $logged,
		'callback'            => function ( WP_REST_Request $request ) {
			$user = cmx_user();
			if ( ! cmx_is_member( $user ) ) {
				return new WP_Error( 'cmx_forbidden', 'Contas de chef e administrador só podem ser removidas pelo administrador.', [ 'status' => 403 ] );
			}
			if ( true !== $request->get_param( 'confirm' ) ) {
				return new WP_Error( 'cmx_confirm', 'Confirme a exclusão.', [ 'status' => 400 ] );
			}
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_logout();
			wp_delete_user( $user->ID );
			return [ 'deleted' => true ];
		},
	] );
} );

// Ao remover um usuário, desfaz os contadores que ele alimentava.
add_action( 'delete_user', function ( int $user_id ) {
	foreach ( cmx_user_favorites( $user_id ) as $post_id ) {
		update_post_meta( $post_id, 'cmx_favorites', max( 0, (int) get_post_meta( $post_id, 'cmx_favorites', true ) - 1 ) );
	}
	foreach ( cmx_user_following( $user_id ) as $chef_id ) {
		update_user_meta( $chef_id, 'cmx_followers', max( 0, cmx_followers_count( $chef_id ) - 1 ) );
	}
} );
