<?php
/**
 * Prepara o site: ativa o ACF PRO e o tema ChefMix, usa links permanentes "/nome-do-post/"
 * e cria a página Termos de uso.
 * Uso: php tools/setup.php [--termos]   (depois rode php tools/seed-demo.php)
 */
require __DIR__ . '/bootstrap.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$acf_plugin = 'advanced-custom-fields-pro/acf.php';
if ( ! is_plugin_active( $acf_plugin ) ) {
	$result = activate_plugin( $acf_plugin );
	cmx_cli( is_wp_error( $result ) ? 'ACF PRO: ' . $result->get_error_message() : 'ACF PRO ativado.' );
} else {
	cmx_cli( 'ACF PRO já estava ativo.' );
}

$theme = basename( dirname( __DIR__ ) );
if ( get_stylesheet() !== $theme ) {
	switch_theme( $theme );
	cmx_cli( "Tema {$theme} ativado." );
} else {
	cmx_cli( 'Tema já estava ativo.' );
}

if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
	update_option( 'permalink_structure', '/%postname%/' );
	cmx_cli( 'Links permanentes: /%postname%/' );
}

// Ícone do site (Configurações → Geral) a partir de assets/brand/icon-512.png.
$icon = cmx_register_site_icon();
if ( is_wp_error( $icon ) ) {
	cmx_cli( 'Ícone do site: ' . $icon->get_error_message() );
} else {
	cmx_cli( $icon ? "Ícone do site cadastrado (anexo #{$icon})." : 'Ícone do site já estava definido.' );
}

// Termos de uso (texto em tools/content/termos-de-uso.html). Edições feitas no painel são mantidas;
// --termos regrava a página com o texto do arquivo.
$privacy = get_privacy_policy_url();
$content = str_replace(
	'{{privacidade}}',
	$privacy ? '<a href="' . esc_url( $privacy ) . '">Política de privacidade</a>' : 'Política de privacidade',
	(string) file_get_contents( __DIR__ . '/content/termos-de-uso.html' )
);
$terms   = get_page_by_path( 'termos-de-uso' );
if ( ! $terms || in_array( '--termos', $argv, true ) ) {
	$id = wp_insert_post( [
		'ID'           => $terms ? $terms->ID : 0,
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Termos de uso',
		'post_name'    => 'termos-de-uso',
		'post_content' => $content,
	], true );
	cmx_cli( is_wp_error( $id ) ? 'Termos de uso: ' . $id->get_error_message() : ( $terms ? 'Termos de uso atualizados.' : 'Página Termos de uso criada.' ) );
} else {
	cmx_cli( 'Termos de uso já existiam (use --termos para regravar o texto).' );
}

// As regras de URL e os papéis são (re)gravados na próxima requisição pelo próprio tema.
delete_option( 'chefmix_rewrite_version' );
delete_option( 'chefmix_roles_version' );
cmx_cli( 'Pronto. Agora rode: php tools/seed-demo.php' );
