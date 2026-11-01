<?php
/**
 * wp-admin: atribuição de chef, colunas da listagem e pequenos ajustes de usabilidade.
 */
defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * "Chef responsável" no lugar da caixa "Autor"
 * O chef atribuído vira o autor da receita e passa a poder editá-la.
 * ---------------------------------------------------------------------- */

add_action( 'add_meta_boxes_receita', function ( WP_Post $post ) {
	remove_meta_box( 'authordiv', 'receita', 'normal' );
	if ( current_user_can( 'edit_others_receitas' ) ) {
		add_meta_box( 'cmx-chef', 'Chef responsável', 'cmx_chef_meta_box', 'receita', 'side', 'high' );
	}
} );

function cmx_chef_meta_box( WP_Post $post ): void {
	$chefs   = get_users( [ 'role' => 'chef', 'orderby' => 'display_name', 'fields' => [ 'ID', 'display_name' ] ] );
	$current = (int) $post->post_author;
	$is_chef = cmx_is_chef( $current );
	?>
	<p>
		<label class="screen-reader-text" for="cmx-chef-select">Chef responsável</label>
		<select id="cmx-chef-select" name="post_author_override" style="width:100%">
			<option value="<?php echo esc_attr( (string) ( $is_chef ? get_current_user_id() : $current ) ); ?>">— Equipe ChefMix (sem chef) —</option>
			<?php foreach ( $chefs as $chef ) : ?>
				<option value="<?php echo esc_attr( (string) $chef->ID ); ?>" <?php selected( $current, (int) $chef->ID ); ?>><?php echo esc_html( $chef->display_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="description">
		O chef poderá editar esta receita e ela aparecerá no perfil público dele.
		<?php if ( ! $chefs ) : ?>
			<br><a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>">Cadastre um usuário com a função "Chef"</a>.
		<?php endif; ?>
	</p>
	<?php
}

/* -------------------------------------------------------------------------
 * Colunas da listagem de receitas
 * ---------------------------------------------------------------------- */

add_filter( 'manage_receita_posts_columns', function ( $columns ) {
	$new = [];
	foreach ( $columns as $key => $label ) {
		if ( 'author' === $key ) {
			continue;
		}
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['cmx_chef']  = 'Chef';
			$new['cmx_steps'] = 'Passos';
			$new['cmx_favs']  = 'Favoritos';
		}
	}
	return $new;
} );

add_action( 'manage_receita_posts_custom_column', function ( $column, $post_id ) {
	switch ( $column ) {
		case 'cmx_chef':
			$chef = cmx_recipe_chef( $post_id );
			echo $chef ? esc_html( $chef->display_name ) : '<span style="color:#888">Equipe ChefMix</span>';
			break;
		case 'cmx_steps':
			$rows = function_exists( 'get_field' ) ? get_field( 'passos', $post_id, false ) : [];
			echo (int) ( is_array( $rows ) ? count( $rows ) : 0 );
			break;
		case 'cmx_favs':
			echo (int) get_post_meta( $post_id, 'cmx_favorites', true );
			break;
	}
}, 10, 2 );

/* -------------------------------------------------------------------------
 * Usuários: contagem de seguidores dos chefs
 * ---------------------------------------------------------------------- */

add_filter( 'manage_users_columns', function ( $columns ) {
	$columns['cmx_followers'] = 'Seguidores';
	return $columns;
} );

add_filter( 'manage_users_custom_column', function ( $output, $column, $user_id ) {
	if ( 'cmx_followers' === $column ) {
		return cmx_is_chef( $user_id ) ? (string) cmx_followers_count( (int) $user_id ) : '—';
	}
	return $output;
}, 10, 3 );

/* -------------------------------------------------------------------------
 * Pequenos ajustes de usabilidade no editor de receitas
 * ---------------------------------------------------------------------- */

add_action( 'admin_head-post.php', 'cmx_admin_editor_css' );
add_action( 'admin_head-post-new.php', 'cmx_admin_editor_css' );

function cmx_admin_editor_css(): void {
	if ( 'receita' !== get_current_screen()->post_type ) {
		return;
	}
	?>
	<style>
		#acf-group_cmx_receita .acf-field-repeater[data-name="passos"] > .acf-input > .acf-repeater > table > tbody > .acf-row > .acf-row-handle.order { background: #f3f1ff; font-weight: 700; }
		#acf-group_cmx_receita .acf-field[data-name="pronto"] input { background: #ecfbf5; }
		#acf-group_cmx_receita .acf-field-message .acf-input { background: #f3f1ff; border-radius: 8px; padding: 10px 14px; }
		#acf-group_cmx_receita .acf-field[data-name="acao"] input { font-size: 16px; font-weight: 600; }
	</style>
	<?php
}

// Chefs não criam receitas: some o botão "Adicionar nova" da tela de listagem e do editor.
add_action( 'admin_head', function () {
	if ( current_user_can( 'create_receitas' ) ) {
		return;
	}
	echo '<style>.post-type-receita .page-title-action{display:none!important}</style>';
} );
