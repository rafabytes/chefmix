<?php
/**
 * ChefMix — functions.php
 *
 * Stack do front-end: Parcel + TailwindCSS + React + Swup (código em /src, compilado para /dist).
 */
defined( 'ABSPATH' ) || exit;

define( 'CHEFMIX_VERSION', '1.0.0' );
define( 'CHEFMIX_DIR', get_template_directory() );
define( 'CHEFMIX_URI', get_template_directory_uri() );

foreach ( [
	'helpers',
	'setup',
	'post-types',
	'roles',
	'acf-fields',
	'recipes',
	'routes',
	'social',
	'auth',
	'cache',
	'admin',
] as $module ) {
	require_once CHEFMIX_DIR . "/inc/{$module}.php";
}
