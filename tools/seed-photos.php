<?php
/**
 * Fotos das receitas de demonstração: fotos reais (nada gerado por IA) de bancos de uso livre.
 * Baixa da fonte, recorta em paisagem 4:3 quando a original é vertical, importa na biblioteca
 * com o crédito e define como foto da receita. Pode rodar mais de uma vez (pula o que já está certo).
 *
 * Uso: php tools/seed-demo.php && php tools/seed-photos.php   (--force baixa e importa tudo de novo)
 *
 * Licenças:
 *   Pexels — uso livre, inclusive comercial, sem atribuição obrigatória (creditamos mesmo assim).
 *   CC BY 2.0 — atribuição obrigatória: autor, link da obra e da licença (exibidos na página da receita).
 */
require __DIR__ . '/bootstrap.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

const CMX_PEXELS_LICENSE = [ 'Licença Pexels', 'https://www.pexels.com/license/' ];

/**
 * Foto do Pexels. $focus = centro vertical do recorte (0 topo – 1 base), só para fotos verticais.
 */
function px( int $id, string $page_slug, string $author, string $author_url, float $focus = 0.5 ): array {
	return [
		'url'         => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w=2400",
		'page'        => "https://www.pexels.com/photo/{$page_slug}-{$id}/",
		'site'        => 'Pexels',
		'author'      => $author,
		'author_url'  => $author_url,
		'license'     => CMX_PEXELS_LICENSE[0],
		'license_url' => CMX_PEXELS_LICENSE[1],
		'focus'       => $focus,
	];
}

$photos = [
	'pao-de-queijo-mineiro'         => px( 37778433, 'delicious-brazilian-pao-de-queijo-in-basket', 'Giovanna Kamimura', 'https://www.pexels.com/@giovanna-kamimura-399616174/' ),
	'strogonoff-de-frango'          => px( 40646833, 'delicious-creamy-chicken-with-rice-and-fries', 'Leo Luz Fotografia', 'https://www.pexels.com/@leoluzfotografiabh/', 0.62 ),
	'baiao-de-dois'                 => [
		'url'         => 'https://upload.wikimedia.org/wikipedia/commons/4/44/Bai%C3%A3o_de_dois.jpg',
		'page'        => 'https://commons.wikimedia.org/wiki/File:Bai%C3%A3o_de_dois.jpg',
		'site'        => 'Wikimedia Commons',
		'author'      => 'Zé Carlos Barretta',
		'author_url'  => 'https://www.flickr.com/photos/zebarretta_stock/11174497586/',
		'license'     => 'CC BY 2.0',
		'license_url' => 'https://creativecommons.org/licenses/by/2.0/',
		'focus'       => 0.5,
	],
	'escondidinho-de-carne-de-sol'  => px( 36580989, 'rustic-baked-dish-in-yellow-ceramic-pot', 'Caio Niceas', 'https://www.pexels.com/@caio-niceas-2148806704/' ),
	'risoto-de-cogumelos'           => px( 31779539, 'delicious-mushroom-risotto-with-parmesan-cheese', 'Adriano Bragi', 'https://www.pexels.com/@adriano-bragi-1288014591/' ),
	'quiche-lorraine'               => px( 34318143, 'delicious-cheese-quiche-with-fresh-tomatoes-and-parsley', 'Bia Metidieri', 'https://www.pexels.com/@bia-metidieri-2156623321/' ),
	'chili-com-carne'               => px( 32667184, 'hearty-mexican-chili-with-tortilla-and-avocado', 'Magda Ehlers', 'https://www.pexels.com/@magda-ehlers-pexels/' ),
	'bobo-de-camarao'               => px( 24246337, 'close-up-of-a-meal', 'one click', 'https://www.pexels.com/@rafaellenss/', 0.45 ),
	'creme-de-abobora-com-gengibre' => px( 1277483, 'shallow-focus-photography-of-squash-soup', 'Valeria Boltneva', 'https://www.pexels.com/@valeriya/' ),
	'brigadeiro-de-colher'          => px( 17303221, 'chocolate-ice-cream-served-in-a-restaurant', 'Orhan Pergel', 'https://www.pexels.com/@skylake/', 0.42 ),
];

/**
 * Recorta fotos verticais/quadradas em 4:3 (centradas em $focus) e limita a largura a 2400 px.
 * Devolve o caminho do JPEG gerado (o editor acrescenta .jpg ao .tmp baixado).
 */
function cmx_photo_landscape( string $file, float $focus ): string {
	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		return $file;
	}
	[ 'width' => $w, 'height' => $h ] = $editor->get_size();
	if ( $w / $h < 4 / 3 ) {
		$crop_h = (int) round( $w * 3 / 4 );
		$y      = (int) round( min( max( $h * $focus - $crop_h / 2, 0 ), $h - $crop_h ) );
		$editor->crop( 0, $y, $w, $crop_h );
	}
	$editor->resize( 2400, null );
	$editor->set_quality( 86 );
	$saved = $editor->save( $file . '.jpg', 'image/jpeg' );
	if ( is_wp_error( $saved ) ) {
		return $file;
	}
	wp_delete_file( $file );
	return $saved['path'];
}

$force = in_array( '--force', $argv, true );

$done = 0;
foreach ( $photos as $slug => $photo ) {
	$post = get_page_by_path( $slug, OBJECT, 'receita' );
	if ( ! $post ) {
		cmx_cli( "  – {$slug}: receita não encontrada (rode tools/seed-demo.php)" );
		continue;
	}

	$current = (int) get_post_thumbnail_id( $post );
	$credit  = $current ? get_post_meta( $current, 'cmx_credito', true ) : null;
	if ( ! $force && is_array( $credit ) && ( $credit['page'] ?? '' ) === $photo['page'] ) {
		cmx_cli( "  = {$post->post_title}" );
		++$done;
		continue;
	}

	$tmp = download_url( $photo['url'], 120 );
	if ( is_wp_error( $tmp ) ) {
		cmx_cli( "  ✗ {$post->post_title}: " . $tmp->get_error_message() );
		continue;
	}
	$tmp  = cmx_photo_landscape( $tmp, $photo['focus'] );
	$file = [ 'name' => $slug . '.jpg', 'tmp_name' => $tmp ];
	$id   = media_handle_sideload( $file, $post->ID, $post->post_title, [
		'post_excerpt' => sprintf( 'Foto: %s · %s · %s', $photo['author'], $photo['site'], $photo['license'] ),
	] );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		cmx_cli( "  ✗ {$post->post_title}: " . $id->get_error_message() );
		continue;
	}

	update_post_meta( $id, '_wp_attachment_image_alt', $post->post_title );
	update_post_meta( $id, 'cmx_credito', array_diff_key( $photo, [ 'url' => 1, 'focus' => 1 ] ) );
	set_post_thumbnail( $post, $id );

	// A foto anterior só é apagada se também veio deste script (fotos enviadas pela equipe ficam).
	if ( is_array( $credit ) ) {
		wp_delete_attachment( $current, true );
	}

	cmx_cli( "  ✓ {$post->post_title} — {$photo['author']} ({$photo['site']})" );
	++$done;
}

cmx_cli( "Fotos: {$done}/" . count( $photos ) );
