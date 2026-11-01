<?php
/**
 * Páginas simples (ex.: Política de privacidade).
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article class="container pt-10 sm:pt-14">
		<div class="glass-card mx-auto max-w-3xl rounded-5xl p-7 sm:p-12">
			<h1 class="page-title"><?php the_title(); ?></h1>
			<div class="prose-cmx mt-8"><?php the_content(); ?></div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
