<?php
/**
 * Cabeçalho global (fica fora do contêiner do Swup e persiste entre as navegações).
 */
defined( 'ABSPATH' ) || exit;

$nav = apply_filters( 'chefmix_nav_items', [
	[ 'label' => 'Receitas', 'url' => get_post_type_archive_link( 'receita' ) ],
	[ 'label' => 'Chefs', 'url' => home_url( '/chefs/' ) ],
	[ 'label' => 'Como funciona', 'url' => home_url( '/#como-funciona' ) ],
] );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#swup">Pular para o conteúdo</a>

<div class="aurora" aria-hidden="true">
	<span class="blob left-[-10%] top-[-12%] h-[46vmax] w-[46vmax] bg-iris-200"></span>
	<span class="blob right-[-14%] top-[8%] h-[40vmax] w-[40vmax] bg-mint-100 [animation-delay:-6s]"></span>
	<span class="blob bottom-[-18%] left-[20%] h-[44vmax] w-[44vmax] bg-peach-100 [animation-delay:-12s]"></span>
	<span class="blob bottom-[10%] right-[10%] h-[24vmax] w-[24vmax] bg-sky-100 [animation-delay:-3s]"></span>
</div>

<div class="flex min-h-screen flex-col">
	<header class="sticky top-0 z-40 px-3 pt-3 sm:px-5" data-site-header>
		<div class="glass mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 rounded-full pl-5 pr-2.5">
			<?php get_template_part( 'template-parts/logo' ); ?>

			<nav class="hidden items-center gap-1 md:flex" aria-label="Principal">
				<?php foreach ( $nav as $item ) : ?>
					<a class="nav-link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="flex items-center gap-1.5">
				<button type="button" class="icon-btn" data-search-open aria-label="Buscar receitas">
					<?php echo cmx_icon( 'search', 'h-5 w-5' ); // phpcs:ignore ?>
				</button>
				<?php get_template_part( 'template-parts/auth-chip' ); ?>
			</div>
		</div>
	</header>

	<div class="search-sheet" data-search-sheet hidden>
		<div class="absolute inset-0 bg-ink/20 backdrop-blur-sm" data-search-close></div>
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="glass relative mx-auto mt-24 flex w-[calc(100%-1.5rem)] max-w-2xl items-center gap-3 rounded-full p-2 pl-6 animate-sheet-in">
			<?php echo cmx_icon( 'search', 'h-5 w-5 text-ink-mute' ); // phpcs:ignore ?>
			<label class="sr-only" for="cmx-search">Buscar receitas</label>
			<input id="cmx-search" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Risoto, camarão, sem glúten…" class="min-w-0 flex-1 bg-transparent py-3 text-lg font-medium outline-none placeholder:text-ink-mute" autocomplete="off" enterkeyhint="search">
			<button type="submit" class="btn btn-primary">Buscar</button>
		</form>
	</div>

	<main id="swup" class="transition-fade flex-1 pb-28 md:pb-0" tabindex="-1">
