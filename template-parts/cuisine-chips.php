<?php
/**
 * Chips de cozinhas (rolagem horizontal no celular).
 *
 * @var array $args { current: string slug }
 */
defined( 'ABSPATH' ) || exit;

$current = $args['current'] ?? '';
$terms   = get_terms( [ 'taxonomy' => 'cozinha', 'hide_empty' => true ] );
if ( is_wp_error( $terms ) || ! $terms ) {
	return;
}
?>
<div class="-mx-5 overflow-x-auto px-5 scrollbar-none sm:mx-0 sm:px-0">
	<div class="flex w-max gap-2 pb-1 sm:w-auto sm:flex-wrap">
		<a href="<?php echo esc_url( get_post_type_archive_link( 'receita' ) ); ?>" class="chip-filter" <?php echo ( '' === $current && is_post_type_archive( 'receita' ) ) ? 'aria-current="page"' : ''; ?>>Todas</a>
		<?php foreach ( $terms as $term ) : ?>
			<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="chip-filter" style="<?php echo esc_attr( cmx_tone_style( cmx_cuisine_style( $term ) ) ); ?>" <?php echo $current === $term->slug ? 'aria-current="page"' : ''; ?>>
				<span class="chip-dot"></span>
				<?php echo esc_html( $term->name ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</div>
