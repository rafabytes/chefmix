<?php
/**
 * Card de receita.
 *
 * @var array $args { post: WP_Post|int }
 */
defined( 'ABSPATH' ) || exit;

$card = cmx_recipe_card( $args['post'] ?? get_post() );
$chef = $card['chef'];

ob_start();
?>
<span class="absolute left-3 top-3 flex flex-wrap gap-1.5">
	<?php if ( $card['cuisine'] ) : ?>
		<span class="chip-glass"><?php echo esc_html( $card['cuisine']->name ); ?></span>
	<?php endif; ?>
</span>
<span class="absolute bottom-3 left-3 flex flex-wrap gap-1.5">
	<?php if ( $card['minutes'] ) : ?>
		<span class="chip-glass"><?php echo cmx_icon( 'clock', 'h-3.5 w-3.5' ); // phpcs:ignore ?><?php echo esc_html( cmx_minutes( $card['minutes'] ) ); ?></span>
	<?php endif; ?>
	<span class="chip-glass"><?php echo esc_html( sprintf( '%d passos', $card['steps'] ) ); ?></span>
</span>
<?php
$slot = ob_get_clean();
?>
<article class="group relative animate-fade-up">
	<a href="<?php echo esc_url( $card['url'] ); ?>" class="block rounded-4xl focus-visible:outline-offset-4">
		<?php
		get_template_part( 'template-parts/cover', null, [
			'style' => $card['style'],
			'image' => $card['image'],
			'class' => 'aspect-[4/3] rounded-4xl transition duration-500 group-hover:-translate-y-1 group-hover:shadow-float',
			'slot'  => $slot,
		] );
		?>
		<div class="px-1.5 pt-4">
			<h3 class="text-lg font-bold leading-snug tracking-tight"><?php echo esc_html( $card['title'] ); ?></h3>
			<?php if ( $card['excerpt'] ) : ?>
				<p class="mt-1 line-clamp-2 text-sm leading-relaxed text-ink-mute"><?php echo esc_html( $card['excerpt'] ); ?></p>
			<?php endif; ?>
			<div class="mt-3 flex items-center gap-2 text-sm">
				<?php if ( $chef ) : ?>
					<?php echo cmx_chef_avatar( $chef->ID, 'h-6 w-6 text-[10px]' ); // phpcs:ignore ?>
					<span class="truncate font-medium text-ink-soft"><?php echo esc_html( $chef->display_name ); ?></span>
				<?php else : ?>
					<span class="logo-orb h-6 w-6" aria-hidden="true"><?php echo cmx_icon( 'sparkle', 'h-3.5 w-3.5' ); // phpcs:ignore ?></span>
					<span class="truncate font-medium text-ink-soft">Equipe ChefMix</span>
				<?php endif; ?>
				<span class="text-ink-mute">·</span>
				<span class="text-ink-mute"><?php echo esc_html( cmx_difficulty_label( $card['difficulty'] ) ); ?></span>
			</div>
		</div>
	</a>
	<div class="absolute right-3 top-3">
		<?php cmx_island( 'FavoriteButton', [ 'id' => $card['id'], 'count' => $card['favorites'], 'variant' => 'glass' ] ); ?>
	</div>
</article>
