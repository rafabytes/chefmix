<?php
/**
 * Salvos (/salvos/): receitas favoritas, chefs seguidos e conta.
 * Não é um perfil público — só a própria pessoa vê esta página.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$user = cmx_user();
?>

<section class="container pt-10 sm:pt-14">
	<?php if ( ! $user ) : ?>
		<?php
		get_template_part( 'template-parts/empty', null, [
			'icon'  => 'heart',
			'title' => 'Guarde o que você ama',
			'text'  => 'Entre para salvar receitas e seguir chefs. Leva segundos com o Google.',
			'html'  => '<button type="button" class="btn btn-accent btn-lg mt-6" data-login-open data-login-reason="Entre para ver suas receitas salvas e os chefs que você segue.">Entrar ou criar conta</button>',
		] );
		?>
	<?php else :
		$favorites = cmx_user_favorites( $user->ID );
		$following = cmx_user_following( $user->ID );
		$recipes   = $favorites ? get_posts( [ 'post_type' => 'receita', 'post__in' => $favorites, 'orderby' => 'post__in', 'numberposts' => -1 ] ) : [];
		$chefs     = $following ? get_users( [ 'include' => $following, 'role' => 'chef' ] ) : [];
		?>
		<header>
			<p class="eyebrow">Olá, <?php echo esc_html( $user->first_name ?: strtok( $user->display_name, ' ' ) ); ?></p>
			<h1 class="page-title">Salvos</h1>
		</header>

		<h2 class="mt-12 text-2xl font-bold tracking-tight">Receitas favoritas</h2>
		<?php if ( $recipes ) : ?>
			<div class="mt-6 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
				<?php
				foreach ( $recipes as $recipe ) {
					get_template_part( 'template-parts/recipe-card', null, [ 'post' => $recipe ] );
				}
				?>
			</div>
		<?php else : ?>
			<p class="glass-card mt-6 rounded-3xl p-6 text-ink-soft">Toque no <?php echo cmx_icon( 'heart', 'inline h-4 w-4 align-[-2px]' ); // phpcs:ignore ?> de uma receita para guardá-la aqui. <a class="link" href="<?php echo esc_url( get_post_type_archive_link( 'receita' ) ); ?>">Explorar receitas</a></p>
		<?php endif; ?>

		<h2 class="mt-16 text-2xl font-bold tracking-tight">Chefs que você segue</h2>
		<?php if ( $chefs ) : ?>
			<div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
				<?php
				foreach ( $chefs as $chef ) {
					get_template_part( 'template-parts/chef-card', null, [ 'user' => $chef ] );
				}
				?>
			</div>
		<?php else : ?>
			<p class="glass-card mt-6 rounded-3xl p-6 text-ink-soft">Você ainda não segue nenhum chef. <a class="link" href="<?php echo esc_url( home_url( '/chefs/' ) ); ?>">Conhecer os chefs</a></p>
		<?php endif; ?>

		<h2 class="mt-16 text-2xl font-bold tracking-tight">Conta</h2>
		<div class="glass-card mt-6 flex flex-col gap-5 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
			<div>
				<p class="font-semibold"><?php echo esc_html( $user->display_name ); ?></p>
				<p class="text-sm text-ink-mute"><?php echo esc_html( $user->user_email ); ?></p>
			</div>
			<div class="flex flex-wrap gap-2">
				<a class="btn btn-glass" data-no-swup href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php echo cmx_icon( 'logout', 'h-4 w-4' ); // phpcs:ignore ?> Sair</a>
				<?php if ( cmx_is_member( $user ) ) : ?>
					<?php cmx_island( 'DeleteAccount' ); ?>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();
