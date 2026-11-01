<?php
/**
 * Card de chef.
 *
 * @var array $args { user: WP_User }
 */
defined( 'ABSPATH' ) || exit;

$chef      = $args['user'];
$count     = (int) count_user_posts( $chef->ID, 'receita', true );
$followers = cmx_followers_count( $chef->ID );
$spec      = function_exists( 'get_field' ) ? (string) get_field( 'especialidade', 'user_' . $chef->ID ) : '';
?>
<article class="glass-card group relative flex flex-col items-center rounded-4xl p-6 text-center animate-fade-up">
	<a href="<?php echo esc_url( cmx_chef_url( $chef->ID ) ); ?>" class="absolute inset-0 rounded-4xl" aria-label="<?php echo esc_attr( 'Ver perfil de ' . $chef->display_name ); ?>"></a>
	<?php echo cmx_chef_avatar( $chef->ID, 'h-20 w-20 text-xl' ); // phpcs:ignore ?>
	<h3 class="mt-4 text-lg font-bold tracking-tight"><?php echo esc_html( $chef->display_name ); ?></h3>
	<?php if ( $spec ) : ?>
		<p class="mt-1 line-clamp-2 text-sm text-ink-mute"><?php echo esc_html( $spec ); ?></p>
	<?php endif; ?>
	<p class="mt-3 text-xs font-semibold uppercase tracking-wider text-ink-mute">
		<?php echo esc_html( sprintf( _n( '%d receita', '%d receitas', $count, 'chefmix' ), $count ) ); ?>
	</p>
	<div class="relative z-10 mt-4">
		<?php cmx_island( 'FollowButton', [ 'id' => $chef->ID, 'count' => $followers, 'size' => 'sm' ] ); ?>
	</div>
</article>
