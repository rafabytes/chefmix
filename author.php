<?php
/**
 * Perfil público do chef (/chef/slug/). Usuários que não são chefs recebem 404 (inc/roles.php).
 */
defined( 'ABSPATH' ) || exit;

get_header();

$chef      = get_queried_object();
$has_acf   = function_exists( 'get_field' );
$spec      = $has_acf ? (string) get_field( 'especialidade', 'user_' . $chef->ID ) : '';
$city      = $has_acf ? (string) get_field( 'cidade', 'user_' . $chef->ID ) : '';
$instagram = $has_acf ? ltrim( (string) get_field( 'instagram', 'user_' . $chef->ID ), '@' ) : '';
$bio       = get_the_author_meta( 'description', $chef->ID );
$count     = (int) count_user_posts( $chef->ID, 'receita', true );
$followers = cmx_followers_count( $chef->ID );
?>

<section class="container pt-10 sm:pt-14">
	<div class="glass relative overflow-hidden rounded-5xl p-7 sm:p-10">
		<div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-iris-200/60 blur-3xl" aria-hidden="true"></div>
		<div class="absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-mint-100/80 blur-3xl" aria-hidden="true"></div>

		<div class="relative flex flex-col gap-8 sm:flex-row sm:items-center">
			<?php echo cmx_chef_avatar( $chef->ID, 'h-28 w-28 text-3xl sm:h-36 sm:w-36 sm:text-4xl' ); // phpcs:ignore ?>
			<div class="flex-1">
				<p class="eyebrow">Chef</p>
				<h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl"><?php echo esc_html( $chef->display_name ); ?></h1>
				<?php if ( $spec ) : ?>
					<p class="mt-2 text-lg text-ink-soft"><?php echo esc_html( $spec ); ?></p>
				<?php endif; ?>
				<div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-ink-soft">
					<?php if ( $city ) : ?>
						<span class="chip-soft"><?php echo cmx_icon( 'pin', 'h-4 w-4' ); // phpcs:ignore ?><?php echo esc_html( $city ); ?></span>
					<?php endif; ?>
					<?php if ( $instagram ) : ?>
						<a class="chip-soft hover:bg-white" href="<?php echo esc_url( 'https://instagram.com/' . rawurlencode( $instagram ) ); ?>" target="_blank" rel="noopener" data-no-swup><?php echo cmx_icon( 'instagram', 'h-4 w-4' ); // phpcs:ignore ?>@<?php echo esc_html( $instagram ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div class="flex items-center gap-6 sm:flex-col sm:items-end">
				<dl class="flex gap-6 text-center sm:text-right">
					<div><dd class="text-3xl font-extrabold tracking-tight"><?php echo (int) $count; ?></dd><dt class="text-xs font-semibold uppercase tracking-wider text-ink-mute">Receitas</dt></div>
					<div><dd class="text-3xl font-extrabold tracking-tight" data-follower-count="<?php echo (int) $chef->ID; ?>"><?php echo (int) $followers; ?></dd><dt class="text-xs font-semibold uppercase tracking-wider text-ink-mute">Seguidores</dt></div>
				</dl>
				<?php cmx_island( 'FollowButton', [ 'id' => $chef->ID, 'count' => $followers ] ); ?>
			</div>
		</div>

		<?php if ( $bio ) : ?>
			<div class="relative mt-8 max-w-3xl border-t border-ink/5 pt-6 text-lg leading-relaxed text-ink-soft">
				<?php echo wp_kses_post( wpautop( $bio ) ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<section class="container mt-14">
	<h2 class="section-title">Receitas de <?php echo esc_html( strtok( $chef->display_name, ' ' ) ); ?></h2>
	<?php if ( have_posts() ) : ?>
		<div class="mt-8 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/recipe-card', null, [ 'post' => get_post() ] );
			endwhile;
			?>
		</div>
	<?php else : ?>
		<?php get_template_part( 'template-parts/empty', null, [ 'icon' => 'chef', 'title' => 'Receitas a caminho', 'text' => 'Este chef ainda não tem receitas publicadas. Siga para saber quando chegarem.' ] ); ?>
	<?php endif; ?>
</section>

<?php
get_footer();
