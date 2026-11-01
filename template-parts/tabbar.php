<?php
/**
 * Barra de abas flutuante (celular). Itens grandes, ao alcance do polegar.
 */
defined( 'ABSPATH' ) || exit;

$tabs = [
	[ 'label' => 'Início', 'icon' => 'home', 'url' => home_url( '/' ) ],
	[ 'label' => 'Receitas', 'icon' => 'grid', 'url' => get_post_type_archive_link( 'receita' ) ],
	[ 'label' => 'Buscar', 'icon' => 'search', 'url' => '' ],
	[ 'label' => 'Chefs', 'icon' => 'chef', 'url' => home_url( '/chefs/' ) ],
	[ 'label' => 'Salvos', 'icon' => 'heart', 'url' => home_url( '/salvos/' ) ],
];
?>
<nav class="tabbar md:hidden" aria-label="Navegação principal" data-tabbar>
	<div class="glass mx-auto flex max-w-md items-stretch justify-between rounded-full p-1.5">
		<?php foreach ( $tabs as $tab ) : ?>
			<?php if ( $tab['url'] ) : ?>
				<a class="tab" href="<?php echo esc_url( $tab['url'] ); ?>">
					<?php echo cmx_icon( $tab['icon'], 'h-[22px] w-[22px]' ); // phpcs:ignore ?>
					<span><?php echo esc_html( $tab['label'] ); ?></span>
				</a>
			<?php else : ?>
				<button type="button" class="tab" data-search-open>
					<?php echo cmx_icon( $tab['icon'], 'h-[22px] w-[22px]' ); // phpcs:ignore ?>
					<span><?php echo esc_html( $tab['label'] ); ?></span>
				</button>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</nav>
