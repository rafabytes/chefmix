<?php
/**
 * Login e cadastro pelo site (modal), sem passar pelo wp-login.php.
 *
 *   POST /chefmix/v1/auth/login     {login, password}
 *   POST /chefmix/v1/auth/register  {name, email, password, company (isca anti-robô)}
 *   POST /chefmix/v1/auth/google    {credential}
 *
 * Google: usa o botão oficial "Fazer login com o Google" (Google Identity Services). O navegador recebe
 * um ID token (JWT assinado pelo Google) e o envia por POST; o servidor confere o token no endpoint
 * tokeninfo do Google (assinatura, validade, público = nosso Client ID). Não há redirecionamento OAuth
 * nem callback com ?code=&state= na URL — o que evita bloqueios de ModSecurity e dispensa a chave secreta.
 *
 * Contas novas nascem como Assinante. Contas de chef/administrador nunca são vinculadas ao Google por e-mail.
 */
defined( 'ABSPATH' ) || exit;

/** Limita tentativas por IP. Retorna WP_Error quando o limite estoura. */
function cmx_throttle( string $action, int $limit, int $window ) {
	$ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0' ) );
	$key = 'cmx_rl_' . md5( $action . '|' . $ip );
	$hit = (int) get_transient( $key );
	if ( $hit >= $limit ) {
		return new WP_Error( 'cmx_rate_limited', 'Muitas tentativas. Aguarde alguns minutos e tente de novo.', [ 'status' => 429 ] );
	}
	set_transient( $key, $hit + 1, $window );
	return true;
}

function cmx_auth_success( WP_User $user ): array {
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true, is_ssl() );
	do_action( 'wp_login', $user->user_login, $user );
	return [ 'ok' => true, 'name' => $user->display_name ];
}

function cmx_unique_login( string $seed ): string {
	$login = sanitize_user( remove_accents( strtolower( $seed ) ), true );
	$login = preg_replace( '/[^a-z0-9._-]/', '', $login ) ?: 'cozinheiro';
	$login = substr( $login, 0, 40 );
	$base  = $login;
	for ( $i = 2; username_exists( $login ); $i++ ) {
		$login = $base . $i;
	}
	return $login;
}

add_action( 'rest_api_init', function () {
	$public = '__return_true';

	register_rest_route( 'chefmix/v1', '/auth/login', [
		'methods'             => 'POST',
		'permission_callback' => $public,
		'callback'            => function ( WP_REST_Request $request ) {
			$limited = cmx_throttle( 'login', 10, 15 * MINUTE_IN_SECONDS );
			if ( is_wp_error( $limited ) ) {
				return $limited;
			}
			$login    = trim( (string) $request->get_param( 'login' ) );
			$password = (string) $request->get_param( 'password' );
			if ( '' === $login || '' === $password ) {
				return new WP_Error( 'cmx_empty', 'Informe e-mail e senha.', [ 'status' => 400 ] );
			}
			if ( is_email( $login ) && ( $by_email = get_user_by( 'email', $login ) ) ) {
				$login = $by_email->user_login;
			}
			$user = wp_authenticate( $login, $password );
			if ( is_wp_error( $user ) ) {
				return new WP_Error( 'cmx_invalid', 'E-mail ou senha incorretos.', [ 'status' => 401 ] );
			}
			return cmx_auth_success( $user );
		},
	] );

	register_rest_route( 'chefmix/v1', '/auth/register', [
		'methods'             => 'POST',
		'permission_callback' => $public,
		'callback'            => function ( WP_REST_Request $request ) {
			if ( ! cmx_opt( 'cadastro_email', true ) ) {
				return new WP_Error( 'cmx_closed', 'O cadastro com e-mail está desativado. Entre com o Google.', [ 'status' => 403 ] );
			}
			if ( '' !== trim( (string) $request->get_param( 'company' ) ) ) {
				return new WP_Error( 'cmx_bot', 'Não foi possível criar a conta.', [ 'status' => 400 ] );
			}
			$limited = cmx_throttle( 'register', 5, HOUR_IN_SECONDS );
			if ( is_wp_error( $limited ) ) {
				return $limited;
			}

			$name     = sanitize_text_field( (string) $request->get_param( 'name' ) );
			$email    = sanitize_email( (string) $request->get_param( 'email' ) );
			$password = (string) $request->get_param( 'password' );

			if ( mb_strlen( $name ) < 2 ) {
				return new WP_Error( 'cmx_name', 'Conte pra gente como você se chama.', [ 'status' => 400 ] );
			}
			if ( ! is_email( $email ) ) {
				return new WP_Error( 'cmx_email', 'Esse e-mail não parece válido.', [ 'status' => 400 ] );
			}
			if ( email_exists( $email ) ) {
				return new WP_Error( 'cmx_email_taken', 'Já existe uma conta com esse e-mail. Entre ou recupere a senha.', [ 'status' => 409 ] );
			}
			if ( strlen( $password ) < 8 ) {
				return new WP_Error( 'cmx_password', 'A senha precisa ter pelo menos 8 caracteres.', [ 'status' => 400 ] );
			}

			$user_id = wp_insert_user( [
				'user_login'   => cmx_unique_login( strstr( $email, '@', true ) ),
				'user_pass'    => $password,
				'user_email'   => $email,
				'display_name' => $name,
				'nickname'     => $name,
				'first_name'   => (string) strtok( $name, ' ' ),
				'role'         => 'subscriber',
			] );
			if ( is_wp_error( $user_id ) ) {
				return new WP_Error( 'cmx_create', 'Não foi possível criar sua conta. Tente novamente.', [ 'status' => 500 ] );
			}
			return cmx_auth_success( get_userdata( $user_id ) );
		},
	] );

	register_rest_route( 'chefmix/v1', '/auth/google', [
		'methods'             => 'POST',
		'permission_callback' => $public,
		'callback'            => function ( WP_REST_Request $request ) {
			$limited = cmx_throttle( 'google', 20, 15 * MINUTE_IN_SECONDS );
			if ( is_wp_error( $limited ) ) {
				return $limited;
			}
			$profile = cmx_google_verify( (string) $request->get_param( 'credential' ) );
			if ( is_wp_error( $profile ) ) {
				return $profile;
			}
			$user = cmx_google_user( $profile );
			if ( is_wp_error( $user ) ) {
				return $user;
			}
			return cmx_auth_success( $user );
		},
	] );
} );

/**
 * Confere o ID token do Google.
 *
 * @return array{sub:string,email:string,name:string,given_name:string}|WP_Error
 */
function cmx_google_verify( string $credential ) {
	$client_id = trim( (string) cmx_opt( 'google_client_id', '' ) );
	if ( '' === $client_id ) {
		return new WP_Error( 'cmx_google_off', 'O login com Google ainda não foi configurado.', [ 'status' => 503 ] );
	}
	if ( ! preg_match( '/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $credential ) ) {
		return new WP_Error( 'cmx_google_token', 'Token do Google inválido.', [ 'status' => 400 ] );
	}

	$response = wp_remote_get( add_query_arg( 'id_token', $credential, 'https://oauth2.googleapis.com/tokeninfo' ), [ 'timeout' => 15 ] );
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'cmx_google_http', 'Não foi possível falar com o Google. Tente novamente.', [ 'status' => 502 ] );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	$valid = 200 === wp_remote_retrieve_response_code( $response )
		&& is_array( $data )
		&& hash_equals( $client_id, (string) ( $data['aud'] ?? '' ) )
		&& in_array( $data['iss'] ?? '', [ 'accounts.google.com', 'https://accounts.google.com' ], true )
		&& (int) ( $data['exp'] ?? 0 ) > time()
		&& ! empty( $data['sub'] );

	if ( ! $valid ) {
		return new WP_Error( 'cmx_google_invalid', 'O Google não confirmou seu login. Tente novamente.', [ 'status' => 401 ] );
	}
	if ( empty( $data['email'] ) || 'true' !== (string) ( $data['email_verified'] ?? '' ) ) {
		return new WP_Error( 'cmx_google_email', 'Sua conta Google precisa ter um e-mail verificado.', [ 'status' => 401 ] );
	}

	return [
		'sub'        => (string) $data['sub'],
		'email'      => (string) $data['email'],
		'name'       => (string) ( $data['name'] ?? '' ),
		'given_name' => (string) ( $data['given_name'] ?? '' ),
	];
}

/** Encontra (pelo ID do Google ou e-mail) ou cria a conta de assinante. A foto do Google não é guardada. */
function cmx_google_user( array $profile ) {
	$found = get_users( [ 'meta_key' => 'cmx_google_sub', 'meta_value' => $profile['sub'], 'number' => 1 ] );
	$user  = $found ? $found[0] : null;

	if ( ! $user && ( $by_email = get_user_by( 'email', $profile['email'] ) ) ) {
		if ( ! cmx_is_member( $by_email ) ) {
			return new WP_Error( 'cmx_google_privileged', 'Este e-mail pertence a uma conta de chef ou administrador. Entre com e-mail e senha.', [ 'status' => 403 ] );
		}
		$user = $by_email;
	}

	if ( ! $user ) {
		$name    = $profile['name'] ?: strstr( $profile['email'], '@', true );
		$user_id = wp_insert_user( [
			'user_login'   => cmx_unique_login( strstr( $profile['email'], '@', true ) ),
			'user_pass'    => wp_generate_password( 32 ),
			'user_email'   => $profile['email'],
			'display_name' => $name,
			'nickname'     => $name,
			'first_name'   => $profile['given_name'] ?: (string) strtok( $name, ' ' ),
			'role'         => 'subscriber',
		] );
		if ( is_wp_error( $user_id ) ) {
			return new WP_Error( 'cmx_create', 'Não foi possível criar sua conta. Tente novamente.', [ 'status' => 500 ] );
		}
		$user = get_userdata( $user_id );
	}

	update_user_meta( $user->ID, 'cmx_google_sub', $profile['sub'] );
	return $user;
}
