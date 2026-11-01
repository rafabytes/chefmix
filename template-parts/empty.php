<?php
/**
 * Estado vazio.
 *
 * @var array $args { icon, title, text, cta: {label,url}, html }
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="glass-card mx-auto mt-10 flex max-w-lg flex-col items-center rounded-5xl px-8 py-12 text-center">
	<span class="logo-orb h-16 w-16"><?php echo cmx_icon( $args['icon'] ?? 'sparkle', 'h-7 w-7' ); // phpcs:ignore ?></span>
	<h2 class="mt-5 text-2xl font-bold tracking-tight"><?php echo esc_html( $args['title'] ?? '' ); ?></h2>
	<?php if ( ! empty( $args['text'] ) ) : ?>
		<p class="mt-2 text-ink-soft"><?php echo esc_html( $args['text'] ); ?></p>
	<?php endif; ?>
	<?php if ( ! empty( $args['cta'] ) ) : ?>
		<a class="btn btn-primary mt-6" href="<?php echo esc_url( $args['cta']['url'] ); ?>"><?php echo esc_html( $args['cta']['label'] ); ?></a>
	<?php endif; ?>
	<?php echo $args['html'] ?? ''; // phpcs:ignore -- HTML montado pelo tema ?>
</div>
