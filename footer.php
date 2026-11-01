<?php
/**
 * Rodapé global + barra de abas do celular + raiz global do React (login e avisos).
 */
defined( 'ABSPATH' ) || exit;
?>
	</main>

	<footer class="mt-24 hidden px-5 pb-10 md:block">
		<div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-6 border-t border-ink/5 pt-8 text-sm text-ink-mute">
			<div class="flex items-center gap-4">
				<?php get_template_part( 'template-parts/logo' ); ?>
				<span>Receitas guiadas, um passo de cada vez.</span>
			</div>
			<nav class="flex flex-wrap items-center gap-5" aria-label="Rodapé">
				<a class="hover:text-ink" href="<?php echo esc_url( get_post_type_archive_link( 'receita' ) ); ?>">Receitas</a>
				<a class="hover:text-ink" href="<?php echo esc_url( home_url( '/chefs/' ) ); ?>">Chefs</a>
				<?php if ( get_privacy_policy_url() ) : ?>
					<a class="hover:text-ink" href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Privacidade</a>
				<?php endif; ?>
				<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> ChefMix</span>
			</nav>
		</div>
	</footer>

	<?php get_template_part( 'template-parts/tabbar' ); ?>
</div>

<div id="cmx-root"></div>
<?php wp_footer(); ?>
</body>
</html>
