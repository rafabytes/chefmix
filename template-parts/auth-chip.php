<?php
/**
 * Entrar / menu do usuário no cabeçalho.
 * Assinantes não têm foto: aparecem com um ícone neutro. Chefs mostram a foto do perfil.
 */
defined( 'ABSPATH' ) || exit;

$user = cmx_user();

if ( ! $user ) : ?>
	<button type="button" class="btn btn-primary h-11 px-5" data-login-open>Entrar</button>
	<?php
	return;
endif;

$first = $user->first_name ?: strtok( $user->display_name, ' ' );
$photo = cmx_chef_photo( $user );
?>
<details class="relative" data-user-menu>
	<summary class="flex h-11 cursor-pointer list-none items-center gap-2 rounded-full bg-white/70 pl-1.5 pr-3.5 ring-1 ring-ink/5 transition hover:bg-white [&::-webkit-details-marker]:hidden" aria-label="Menu da conta">
		<?php if ( $photo ) : ?>
			<img src="<?php echo esc_url( $photo ); ?>" alt="" class="h-8 w-8 rounded-full object-cover">
		<?php else : ?>
			<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-mist-deep text-ink-soft"><?php echo cmx_icon( 'user', 'h-4 w-4' ); // phpcs:ignore ?></span>
		<?php endif; ?>
		<span class="hidden max-w-[8rem] truncate text-sm font-semibold sm:inline"><?php echo esc_html( $first ); ?></span>
		<?php echo cmx_icon( 'chevron-down', 'h-4 w-4 text-ink-mute' ); // phpcs:ignore ?>
	</summary>
	<div class="glass absolute right-0 top-[calc(100%+.6rem)] w-64 rounded-3xl p-2 animate-sheet-in">
		<p class="px-3 pb-2 pt-1.5 text-xs text-ink-mute">Conectado como<br><strong class="text-sm text-ink"><?php echo esc_html( $user->display_name ); ?></strong></p>
		<a class="menu-item" href="<?php echo esc_url( home_url( '/salvos/' ) ); ?>"><?php echo cmx_icon( 'heart', 'h-4 w-4' ); // phpcs:ignore ?> Salvos e chefs seguidos</a>
		<?php if ( cmx_is_chef( $user ) ) : ?>
			<a class="menu-item" href="<?php echo esc_url( cmx_chef_url( $user->ID ) ); ?>"><?php echo cmx_icon( 'chef', 'h-4 w-4' ); // phpcs:ignore ?> Meu perfil público</a>
		<?php endif; ?>
		<?php if ( user_can( $user, 'edit_receitas' ) ) : ?>
			<a class="menu-item" data-no-swup href="<?php echo esc_url( admin_url( 'edit.php?post_type=receita' ) ); ?>"><?php echo cmx_icon( 'list', 'h-4 w-4' ); // phpcs:ignore ?> Painel de receitas</a>
		<?php endif; ?>
		<a class="menu-item text-rose-700" data-no-swup href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php echo cmx_icon( 'logout', 'h-4 w-4' ); // phpcs:ignore ?> Sair</a>
	</div>
</details>
