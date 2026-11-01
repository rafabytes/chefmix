<?php
/**
 * Capa de receita: foto (se houver) ou degradê da cozinha com um orbe de vidro e o ícone.
 *
 * @var array $args { style: array, image: string, class: string, icon_size: string, orb_size: string, slot: string }
 */
defined( 'ABSPATH' ) || exit;

$style     = $args['style'] ?? cmx_cuisine_style( null );
$image     = $args['image'] ?? '';
$class     = $args['class'] ?? 'aspect-[4/3] rounded-4xl';
$icon_size = $args['icon_size'] ?? 'h-10 w-10';
$orb_size  = $args['orb_size'] ?? 'h-24 w-24';
?>
<div class="cover <?php echo esc_attr( $class ); ?>" style="<?php echo esc_attr( cmx_tone_style( $style ) ); ?>">
	<?php if ( $image ) : ?>
		<img src="<?php echo esc_url( $image ); ?>" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
	<?php else : ?>
		<span class="cover-orb <?php echo esc_attr( $orb_size ); ?>" aria-hidden="true"><?php echo cmx_icon( $style['icon'], $icon_size . ' text-ink/80', 1.6 ); // phpcs:ignore ?></span>
	<?php endif; ?>
	<?php echo $args['slot'] ?? ''; // phpcs:ignore -- HTML montado pelo tema ?>
</div>
