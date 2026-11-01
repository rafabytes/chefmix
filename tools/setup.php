<?php
/**
 * Prepara o site: ativa o ACF PRO e o tema ChefMix e usa links permanentes "/nome-do-post/".
 * Uso: php tools/setup.php   (depois rode php tools/seed-demo.php)
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

// As regras de URL e os papéis são (re)gravados na próxima requisição pelo próprio tema.
delete_option( 'chefmix_rewrite_version' );
delete_option( 'chefmix_roles_version' );
cmx_cli( 'Pronto. Agora rode: php tools/seed-demo.php' );
