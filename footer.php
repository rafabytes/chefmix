<?php

/**
 * Rodapé global + barra de abas do celular + raiz global do React (login e avisos).
 *
 * Rodapé padrão dos sites do appMaster:
 *   esquerda — logotipo e slogan do site, ano e direitos (+ termos e privacidade)
 *   direita  — "Gerado por IA, feito por gente" (sobre o appMaster) e "Projeto de código livre" (GitHub)
 */
defined('ABSPATH') || exit;

$terms   = cmx_terms_url();
$privacy = get_privacy_policy_url();
?>
</main>

<footer class="mt-16 px-5 pb-28 md:mt-24 md:pb-10">
	<div class="mx-auto flex max-w-6xl flex-col gap-6 border-t border-ink/5 pt-8 text-sm text-ink-mute md:flex-row md:items-end md:justify-between">
		<div class="space-y-3">
			<p class="flex flex-wrap gap-x-4 gap-y-1">
				<span>© <?php echo esc_html(wp_date('Y')); ?> ChefMix.</span>
				<?php if ($terms) : ?>
					<a class="hover:text-ink" href="<?php echo esc_url($terms); ?>">Termos de uso</a>
				<?php endif; ?>
				<?php if ($privacy) : ?>
					<a class="hover:text-ink" href="<?php echo esc_url($privacy); ?>">Privacidade</a>
				<?php endif; ?>
			</p>
		</div>
		<ul class="space-y-2 md:text-right">
			<li>
				<a class="inline-flex items-center gap-2 hover:text-ink" href="<?php echo esc_url(CMX_REPO_URL); ?>" target="_blank" rel="noopener">
					<?php echo cmx_icon('code', 'h-4 w-4'); // phpcs:ignore 
					?>Código livre
				</a>
			</li>
		</ul>
	</div>
</footer>

<?php get_template_part('template-parts/tabbar'); ?>
</div>

<div id="cmx-root"></div>
<?php wp_footer(); ?>
</body>

</html>