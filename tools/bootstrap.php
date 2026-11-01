<?php
/**
 * Carrega o WordPress pela linha de comando (usado pelos scripts de tools/).
 * Uso: php tools/<script>.php
 */
if ( 'cli' !== PHP_SAPI ) {
	exit( "Rode pela linha de comando.\n" );
}

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';

require dirname( __DIR__, 4 ) . '/wp-load.php';

function cmx_cli( string $message ): void {
	fwrite( STDOUT, $message . "\n" );
}
