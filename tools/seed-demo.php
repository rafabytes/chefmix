<?php
/**
 * Dados de demonstração: 8 cozinhas, 3 chefs, 1 assinante e 10 receitas guiadas.
 * Receitas para utensílios comuns (panela, frigideira, tigela, liquidificador, forno).
 * Pode rodar mais de uma vez (atualiza o que já existe pelo slug/login).
 *
 * Uso: php tools/seed-demo.php
 *
 * Usuários criados (senha: chefmix-demo) — REMOVA antes de ir para produção:
 *   chef-marina, chef-lorenzo, chef-camille (papel Chef) · assinante-demo (Assinante)
 */
require __DIR__ . '/bootstrap.php';

if ( ! function_exists( 'update_field' ) || ! post_type_exists( 'receita' ) ) {
	cmx_cli( 'Ative o ACF PRO e o tema ChefMix antes (php tools/setup.php).' );
	exit( 1 );
}

const CMX_DEMO_PASSWORD = 'chefmix-demo';

/* -------------------------------------------------------------------------
 * Atalhos para escrever as receitas
 * ---------------------------------------------------------------------- */

/** Ingrediente da lista geral (mise en place). $prep = o que fazer antes de começar. */
function i( $qty, string $unit, string $name, string $prep = '', string $group = '' ): array {
	return [ 'grupo' => $group, 'quantidade' => $qty, 'unidade' => $unit, 'nome' => $name, 'obs' => $prep ];
}

/** Ingrediente usado num passo. */
function si( $qty, string $unit, string $name ): array {
	return [ 'quantidade' => $qty, 'unidade' => $unit, 'nome' => $name ];
}

/**
 * Passo.
 * $o: ing, min, seg, fogo, temp, vel, mexer, tampa, pronto, dica, atencao
 */
function s( string $local, string $acao, string $instrucao = '', array $o = [] ): array {
	return [
		'local'        => $local,
		'acao'         => $acao,
		'instrucao'    => $instrucao,
		'ingredientes' => $o['ing'] ?? [],
		'tempo_min'    => $o['min'] ?? '',
		'tempo_seg'    => $o['seg'] ?? '',
		'fogo'         => $o['fogo'] ?? '',
		'temperatura'  => isset( $o['temp'] ) ? (string) $o['temp'] : '',
		'velocidade'   => $o['vel'] ?? '',
		'mexer'        => $o['mexer'] ?? '',
		'tampa'        => $o['tampa'] ?? '',
		'pronto'       => $o['pronto'] ?? '',
		'dica'         => $o['dica'] ?? '',
		'atencao'      => $o['atencao'] ?? '',
	];
}

/* -------------------------------------------------------------------------
 * Cozinhas
 * ---------------------------------------------------------------------- */

$cuisines = [
	'brasileira'    => [ 'Brasileira', 'Clássicos de casa, do café da manhã ao almoço de domingo.' ],
	'nordestina'    => [ 'Nordestina', 'Sertão e litoral: coalho, carne de sol, mandioca e manteiga de garrafa.' ],
	'italiana'      => [ 'Italiana', 'Risotos e massas com técnica simples e muito sabor.' ],
	'francesa'      => [ 'Francesa', 'Bases clássicas sem cerimônia: quiches, cremes e molhos.' ],
	'mexicana'      => [ 'Mexicana', 'Especiarias, pimentas e feijões.' ],
	'frutos-do-mar' => [ 'Frutos do mar', 'Peixes e camarões no ponto certo.' ],
	'sopas'         => [ 'Sopas', 'Cremes e caldos reconfortantes.' ],
	'sobremesas'    => [ 'Sobremesas', 'Doces para fechar a refeição.' ],
];

$term_ids = [];
foreach ( $cuisines as $slug => [ $name, $description ] ) {
	$term = get_term_by( 'slug', $slug, 'cozinha' );
	if ( $term ) {
		wp_update_term( $term->term_id, 'cozinha', [ 'name' => $name, 'description' => $description ] );
		$term_ids[ $slug ] = $term->term_id;
	} else {
		$created           = wp_insert_term( $name, 'cozinha', [ 'slug' => $slug, 'description' => $description ] );
		$term_ids[ $slug ] = $created['term_id'];
	}
	[ $tone, $icon ] = cmx_cuisine_defaults()[ $slug ];
	update_term_meta( $term_ids[ $slug ], 'tom', $tone );
	update_term_meta( $term_ids[ $slug ], 'icone', $icon );
}
cmx_cli( 'Cozinhas: ' . count( $term_ids ) );

/* -------------------------------------------------------------------------
 * Usuários
 * ---------------------------------------------------------------------- */

function cmx_demo_user( string $login, string $name, string $role, array $meta = [], string $bio = '' ): int {
	$user = get_user_by( 'login', $login );
	$data = [
		'user_login'   => $login,
		'user_email'   => $login . '@example.com',
		'display_name' => $name,
		'nickname'     => $name,
		'first_name'   => strtok( $name, ' ' ),
		'last_name'    => trim( (string) strstr( $name, ' ' ) ),
		'description'  => $bio,
		'role'         => $role,
	];
	if ( $user ) {
		$data['ID'] = $user->ID;
		$id         = wp_update_user( $data );
	} else {
		$data['user_pass'] = CMX_DEMO_PASSWORD;
		$id                = wp_insert_user( $data );
	}
	if ( is_wp_error( $id ) ) {
		cmx_cli( "Erro ao criar {$login}: " . $id->get_error_message() );
		exit( 1 );
	}
	foreach ( $meta as $key => $value ) {
		update_field( $key, $value, 'user_' . $id );
	}
	return (int) $id;
}

$marina = cmx_demo_user( 'chef-marina', 'Marina Albuquerque', 'chef', [
	'especialidade' => 'Cozinha nordestina contemporânea',
	'cidade'        => 'Recife, PE',
], 'Cresceu entre o sertão e o litoral e cozinha o Nordeste com técnica e afeto. Acredita que manteiga de garrafa resolve quase tudo e que todo mundo consegue fazer um bom baião em casa — basta seguir o passo a passo.' );

$lorenzo = cmx_demo_user( 'chef-lorenzo', 'Lorenzo Bianchi', 'chef', [
	'especialidade' => 'Risotos, massas e sopas de casa',
	'cidade'        => 'São Paulo, SP',
], 'Neto de italianos, aprendeu a cozinhar mexendo risoto ao lado da nonna. Hoje transforma as receitas de família em passos claros, para quem nunca fez um risoto acertar o ponto de primeira.' );

$camille = cmx_demo_user( 'chef-camille', 'Camille Duarte', 'chef', [
	'especialidade' => 'Confeitaria e bistrô francês',
	'cidade'        => 'Rio de Janeiro, RJ',
], 'Confeiteira formada em Lyon, gosta de receitas francesas que cabem num dia de semana. Precisão nas medidas, leveza no resto.' );

$member = cmx_demo_user( 'assinante-demo', 'Ana Ribeiro', 'subscriber' );

$admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ] );
$team   = $admins ? (int) $admins[0]->ID : 1;

cmx_cli( 'Usuários: 3 chefs + 1 assinante (senha ' . CMX_DEMO_PASSWORD . ')' );

/* -------------------------------------------------------------------------
 * Receitas
 * ---------------------------------------------------------------------- */

$recipes = [

	/* 1 ------------------------------------------------------------------ */
	[
		'slug'        => 'pao-de-queijo-mineiro',
		'title'       => 'Pão de queijo mineiro',
		'excerpt'     => 'Casquinha crocante, miolo puxa-puxa e dois queijos. Polvilho escaldado na tigela, sem segredo.',
		'author'      => $team,
		'cozinhas'    => [ 'brasileira' ],
		'porcoes'     => 30,
		'rendimento'  => 'unidades',
		'dificuldade' => 'facil',
		'preparo'     => 30,
		'total'       => 65,
		'utensilios'  => "Panela pequena\nTigela grande\nColher de pau\nRalador\nAssadeira grande",
		'ingredientes' => [
			i( 250, 'ml', 'leite' ),
			i( 100, 'ml', 'óleo' ),
			i( 1, 'colher chá', 'sal' ),
			i( 250, 'g', 'polvilho doce', 'na tigela grande' ),
			i( 250, 'g', 'polvilho azedo', 'na mesma tigela' ),
			i( 3, 'unidade', 'ovos', 'em temperatura ambiente' ),
			i( 200, 'g', 'queijo minas meia cura', 'ralado grosso' ),
			i( 50, 'g', 'parmesão', 'ralado fino' ),
		],
		'passos' => [
			s( 'forno', 'Preaqueça o forno', 'Ligue agora: ele leva uns 15 minutos para chegar na temperatura.', [ 'temp' => 180 ] ),
			s( 'panela', 'Ferva leite, óleo e sal', '', [
				'ing'    => [ si( 250, 'ml', 'leite' ), si( 100, 'ml', 'óleo' ), si( 1, 'colher chá', 'sal' ) ],
				'fogo'   => 'medio', 'mexer' => 'vezes', 'tampa' => 'sem',
				'pronto' => 'subirem as primeiras bolhas nas bordas',
			] ),
			s( 'tigela', 'Escalde o polvilho', 'Despeje o líquido fervendo sobre o polvilho e misture com a colher de pau.', [
				'pronto' => 'não houver mais polvilho seco e a massa ficar empelotada e úmida',
				'dica'   => 'Parece errado no começo — é assim mesmo. Continue misturando.',
			] ),
			s( 'descanso', 'Deixe a massa amornar', 'Espalhe a massa nas laterais da tigela para esfriar mais rápido.', [
				'min'    => 10,
				'pronto' => 'der para tocar na massa sem queimar o dedo',
			] ),
			s( 'tigela', 'Junte os ovos', 'Um de cada vez, misturando bem antes de colocar o próximo.', [
				'ing'    => [ si( 3, 'unidade', 'ovos' ) ],
				'pronto' => 'a massa ficar lisa e brilhante',
			] ),
			s( 'tigela', 'Sove com os queijos', 'Misture com as mãos, apertando a massa contra o fundo da tigela.', [
				'ing'    => [ si( 200, 'g', 'meia cura ralado' ), si( 50, 'g', 'parmesão ralado' ) ],
				'min'    => 3,
				'pronto' => 'a massa ficar macia e soltar das mãos untadas',
			] ),
			s( 'bancada', 'Modele as bolinhas', 'Unte as mãos com óleo e faça bolinhas de 3 cm. Deixe 3 cm entre elas na assadeira.', [
				'dica' => 'Para congelar: congele cruas na assadeira e depois passe para um saco. Asse direto do congelador, 10 minutos a mais.',
			] ),
			s( 'forno', 'Asse até dourar', '', [
				'min' => 25, 'temp' => 180,
				'pronto' => 'estiverem crescidos e dourados por baixo',
			] ),
		],
	],

	/* 2 ------------------------------------------------------------------ */
	[
		'slug'        => 'strogonoff-de-frango',
		'title'       => 'Strogonoff de frango',
		'excerpt'     => 'O clássico de domingo pronto em meia hora, numa frigideira só, cremoso e sem talhar.',
		'author'      => $team,
		'cozinhas'    => [ 'brasileira' ],
		'porcoes'     => 4,
		'dificuldade' => 'facil',
		'preparo'     => 15,
		'total'       => 35,
		'utensilios'  => "Frigideira grande ou panela larga\nTigela\nColher de pau",
		'ingredientes' => [
			i( 600, 'g', 'peito de frango', 'em cubos de 2 cm' ),
			i( 1, 'colher chá', 'páprica doce' ),
			i( 1, 'colher chá', 'sal' ),
			i( 30, 'g', 'manteiga' ),
			i( 1, 'unidade', 'cebola', 'picada' ),
			i( 2, 'dente', 'alho', 'picados' ),
			i( 200, 'g', 'champignon', 'fatiado' ),
			i( 2, 'colher sopa', 'ketchup' ),
			i( 1, 'colher sopa', 'mostarda' ),
			i( 200, 'g', 'creme de leite' ),
			i( null, 'a gosto', 'pimenta-do-reino' ),
		],
		'passos' => [
			s( 'tigela', 'Tempere o frango', 'Misture bem e deixe ao lado do fogão.', [
				'ing' => [ si( 600, 'g', 'frango em cubos' ), si( 1, 'colher chá', 'páprica doce' ), si( 1, 'colher chá', 'sal' ) ],
			] ),
			s( 'frigideira', 'Refogue cebola e alho na manteiga', '', [
				'ing'    => [ si( 30, 'g', 'manteiga' ), si( 1, 'unidade', 'cebola picada' ), si( 2, 'dente', 'alho picados' ) ],
				'min'    => 3, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'a cebola estiver transparente',
			] ),
			s( 'frigideira', 'Doure o frango', 'Espalhe os cubos numa camada só.', [
				'ing'    => [ si( 600, 'g', 'frango temperado' ) ],
				'min'    => 8, 'fogo' => 'alto', 'mexer' => 'vezes', 'tampa' => 'sem',
				'pronto' => 'estiver dourado por fora e sem partes rosadas',
				'dica'   => 'Não mexa nos primeiros 2 minutos: é assim que ele doura em vez de cozinhar no vapor.',
			] ),
			s( 'frigideira', 'Junte cogumelos e molhos', '', [
				'ing'    => [ si( 200, 'g', 'champignon' ), si( 2, 'colher sopa', 'ketchup' ), si( 1, 'colher sopa', 'mostarda' ) ],
				'min'    => 5, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'os cogumelos murcharem',
			] ),
			s( 'frigideira', 'Finalize com o creme de leite', '', [
				'ing'     => [ si( 200, 'g', 'creme de leite' ) ],
				'min'     => 2, 'fogo' => 'baixo', 'mexer' => 'sempre',
				'pronto'  => 'estiver quente e cremoso',
				'atencao' => 'Não deixe ferver depois do creme de leite: o molho talha.',
				'dica'    => 'Prove e acerte o sal e a pimenta.',
			] ),
			s( 'servir', 'Sirva', 'Com arroz branco e batata palha.' ),
		],
	],

	/* 3 ------------------------------------------------------------------ */
	[
		'slug'        => 'baiao-de-dois',
		'title'       => 'Baião de dois',
		'excerpt'     => 'Arroz e feijão-de-corda com queijo coalho, calabresa e manteiga de garrafa — tudo numa panela só.',
		'author'      => $marina,
		'cozinhas'    => [ 'nordestina' ],
		'porcoes'     => 6,
		'dificuldade' => 'media',
		'preparo'     => 25,
		'total'       => 60,
		'utensilios'  => "Panela média com tampa\nColher de pau\nTravessa para servir",
		'ingredientes' => [
			i( 150, 'g', 'bacon', 'em cubos' ),
			i( 200, 'g', 'linguiça calabresa', 'em rodelas' ),
			i( 1, 'unidade', 'cebola', 'picada' ),
			i( 3, 'dente', 'alho', 'picados' ),
			i( 0.5, 'unidade', 'pimentão verde', 'picado' ),
			i( 300, 'g', 'arroz agulhinha' ),
			i( 400, 'g', 'feijão-de-corda cozido', 'escorrido' ),
			i( 700, 'ml', 'caldo do feijão', 'quente (ou água quente)' ),
			i( 1, 'colher chá', 'sal' ),
			i( 250, 'g', 'queijo coalho', 'em cubos' ),
			i( 2, 'colher sopa', 'manteiga de garrafa' ),
			i( 0.5, 'maço', 'coentro', 'picado' ),
		],
		'passos' => [
			s( 'panela', 'Frite bacon e calabresa', 'Sem óleo: o bacon solta a própria gordura.', [
				'ing'     => [ si( 150, 'g', 'bacon' ), si( 200, 'g', 'calabresa' ) ],
				'min'     => 8, 'fogo' => 'medio', 'mexer' => 'vezes', 'tampa' => 'sem',
				'pronto'  => 'o bacon estiver dourado',
				'atencao' => 'A gordura respinga: afaste o rosto ao mexer.',
			] ),
			s( 'panela', 'Refogue os temperos', '', [
				'ing'    => [ si( 1, 'unidade', 'cebola picada' ), si( 3, 'dente', 'alho picados' ), si( 0.5, 'unidade', 'pimentão picado' ) ],
				'min'    => 3, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'a cebola murchar',
			] ),
			s( 'panela', 'Toste o arroz', 'Assim ele pega o sabor da gordura e fica soltinho.', [
				'ing'    => [ si( 300, 'g', 'arroz' ) ],
				'min'    => 2, 'fogo' => 'medio', 'mexer' => 'sempre',
				'pronto' => 'os grãos ficarem brilhantes',
			] ),
			s( 'panela', 'Cozinhe com o feijão e o caldo', '', [
				'ing'    => [ si( 400, 'g', 'feijão-de-corda cozido' ), si( 700, 'ml', 'caldo quente' ), si( 1, 'colher chá', 'sal' ) ],
				'min'    => 18, 'fogo' => 'baixo', 'mexer' => 'nao', 'tampa' => 'com',
				'pronto' => 'o arroz estiver macio e o caldo tiver secado',
				'dica'   => 'Se secar antes do arroz ficar macio, junte 50 ml de água quente.',
			] ),
			s( 'panela', 'Junte coalho e manteiga de garrafa', 'Misture com delicadeza para não desmanchar o queijo.', [
				'ing'    => [ si( 250, 'g', 'queijo coalho' ), si( 2, 'colher sopa', 'manteiga de garrafa' ) ],
				'min'    => 2, 'fogo' => 'baixo', 'mexer' => 'vezes',
				'pronto' => 'o queijo amolecer',
			] ),
			s( 'descanso', 'Descanse com a tampa fechada', 'Com o fogo desligado.', [
				'min'    => 5,
				'pronto' => 'o arroz absorver o resto do caldo',
			] ),
			s( 'servir', 'Sirva com coentro', 'Transfira para a travessa e espalhe o coentro por cima.', [
				'ing' => [ si( 0.5, 'maço', 'coentro picado' ) ],
			] ),
		],
	],

	/* 4 ------------------------------------------------------------------ */
	[
		'slug'        => 'escondidinho-de-carne-de-sol',
		'title'       => 'Escondidinho de carne de sol',
		'excerpt'     => 'Purê de mandioca aveludado sobre carne de sol na manteiga de garrafa, gratinado com coalho.',
		'author'      => $marina,
		'cozinhas'    => [ 'nordestina' ],
		'porcoes'     => 6,
		'dificuldade' => 'media',
		'preparo'     => 35,
		'total'       => 75,
		'utensilios'  => "Frigideira grande\nPanela média\nEspremedor de batata ou garfo\nRefratário de 30 × 20 cm",
		'ingredientes' => [
			i( 500, 'g', 'carne de sol', 'dessalgada, cozida e desfiada', 'Recheio' ),
			i( 1, 'unidade', 'cebola', 'picada', 'Recheio' ),
			i( 2, 'dente', 'alho', 'picados', 'Recheio' ),
			i( 2, 'unidade', 'tomates', 'sem sementes, picados', 'Recheio' ),
			i( 30, 'g', 'manteiga de garrafa', '', 'Recheio' ),
			i( 1, 'kg', 'mandioca cozida', 'quente, sem o fio central', 'Purê' ),
			i( 300, 'ml', 'leite', 'quente', 'Purê' ),
			i( 50, 'g', 'manteiga', '', 'Purê' ),
			i( 1, 'colher chá', 'sal', '', 'Purê' ),
			i( 150, 'g', 'queijo coalho', 'ralado', 'Cobertura' ),
		],
		'passos' => [
			s( 'forno', 'Preaqueça o forno', '', [ 'temp' => 200 ] ),
			s( 'frigideira', 'Refogue os temperos', '', [
				'ing'    => [ si( 30, 'g', 'manteiga de garrafa' ), si( 1, 'unidade', 'cebola picada' ), si( 2, 'dente', 'alho picados' ) ],
				'min'    => 3, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'a cebola estiver transparente',
			] ),
			s( 'frigideira', 'Junte a carne e o tomate', '', [
				'ing'    => [ si( 500, 'g', 'carne de sol desfiada' ), si( 2, 'unidade', 'tomates picados' ) ],
				'min'    => 6, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'o tomate desmanchar e a carne estiver úmida',
				'dica'   => 'Prove antes de salgar: a carne de sol já tem sal.',
			] ),
			s( 'bancada', 'Espalhe o recheio no refratário', 'Distribua por igual, apertando levemente.' ),
			s( 'panela', 'Faça o purê de mandioca', 'Amasse a mandioca ainda quente na panela, junte o leite, a manteiga e o sal.', [
				'ing'     => [ si( 1, 'kg', 'mandioca cozida' ), si( 300, 'ml', 'leite quente' ), si( 50, 'g', 'manteiga' ), si( 1, 'colher chá', 'sal' ) ],
				'min'     => 3, 'fogo' => 'baixo', 'mexer' => 'sempre',
				'pronto'  => 'estiver liso, espesso e soltando do fundo',
				'atencao' => 'Não use liquidificador: a mandioca fica elástica, como chiclete.',
			] ),
			s( 'bancada', 'Cubra com o purê e o queijo', 'Espalhe o purê sobre o recheio e salpique o coalho.', [
				'ing' => [ si( 150, 'g', 'queijo coalho ralado' ) ],
			] ),
			s( 'forno', 'Gratine', '', [
				'min' => 20, 'temp' => 200,
				'pronto' => 'a superfície estiver dourada',
			] ),
		],
	],

	/* 5 ------------------------------------------------------------------ */
	[
		'slug'        => 'risoto-de-cogumelos',
		'title'       => 'Risoto de cogumelos',
		'excerpt'     => 'Cremoso e al dente: caldo quente aos poucos, sem pressa, e manteiga gelada no final.',
		'author'      => $lorenzo,
		'cozinhas'    => [ 'italiana' ],
		'porcoes'     => 4,
		'dificuldade' => 'media',
		'preparo'     => 35,
		'total'       => 40,
		'utensilios'  => "Panela média de fundo grosso\nPanela pequena para o caldo\nConcha\nColher de pau",
		'ingredientes' => [
			i( 1, 'l', 'caldo de legumes' ),
			i( 30, 'g', 'azeite' ),
			i( 1, 'unidade', 'cebola', 'picada fina' ),
			i( 1, 'dente', 'alho', 'picado' ),
			i( 300, 'g', 'arroz arbóreo' ),
			i( 100, 'ml', 'vinho branco seco' ),
			i( 250, 'g', 'cogumelos paris', 'fatiados' ),
			i( 20, 'g', 'funghi seco', 'hidratado em água morna e picado' ),
			i( 30, 'g', 'manteiga', 'gelada, em cubos' ),
			i( 40, 'g', 'parmesão', 'ralado fino' ),
			i( null, 'a gosto', 'sal e pimenta' ),
		],
		'passos' => [
			s( 'panela', 'Aqueça o caldo', 'Na panela pequena. Deixe-o quente no fogão até o fim da receita.', [
				'ing'    => [ si( 1, 'l', 'caldo de legumes' ) ],
				'fogo'   => 'baixo', 'tampa' => 'entreaberta',
				'pronto' => 'estiver fumegando, sem ferver forte',
			] ),
			s( 'panela', 'Refogue cebola e alho', 'Agora na panela média.', [
				'ing'    => [ si( 30, 'g', 'azeite' ), si( 1, 'unidade', 'cebola picada' ), si( 1, 'dente', 'alho picado' ) ],
				'min'    => 3, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'a cebola estiver transparente, sem dourar',
			] ),
			s( 'panela', 'Toste o arroz', '', [
				'ing'    => [ si( 300, 'g', 'arroz arbóreo' ) ],
				'min'    => 2, 'fogo' => 'medio', 'mexer' => 'sempre',
				'pronto' => 'as bordas dos grãos ficarem translúcidas',
			] ),
			s( 'panela', 'Junte o vinho', '', [
				'ing'     => [ si( 100, 'ml', 'vinho branco' ) ],
				'min'     => 1, 'fogo' => 'medio', 'mexer' => 'sempre',
				'pronto'  => 'o cheiro de álcool sumir e o vinho secar',
				'atencao' => 'O vapor do vinho sobe quente: afaste o rosto.',
			] ),
			s( 'panela', 'Cozinhe com o caldo, aos poucos', 'Junte os cogumelos e uma concha de caldo. Quando quase secar, outra concha. Repita até o ponto.', [
				'ing'    => [ si( 250, 'g', 'cogumelos' ), si( 20, 'g', 'funghi hidratado' ) ],
				'min'    => 18, 'fogo' => 'medio', 'mexer' => 'sempre', 'tampa' => 'sem',
				'pronto' => 'o grão estiver macio por fora e firme no centro',
				'dica'   => 'Pode sobrar caldo — ou faltar. Vale o ponto do arroz, não a medida.',
			] ),
			s( 'panela', 'Manteque fora do fogo', 'Desligue o fogo e junte a manteiga gelada e o parmesão, mexendo com energia.', [
				'ing'    => [ si( 30, 'g', 'manteiga gelada' ), si( 40, 'g', 'parmesão ralado' ) ],
				'min'    => 1, 'mexer' => 'sempre',
				'pronto' => 'estiver cremoso e escorrendo devagar na colher',
			] ),
			s( 'servir', 'Sirva já', 'Acerte sal e pimenta. Risoto não espera: leve à mesa em seguida.' ),
		],
	],

	/* 6 ------------------------------------------------------------------ */
	[
		'slug'        => 'quiche-lorraine',
		'title'       => 'Quiche lorraine',
		'excerpt'     => 'Massa amanteigada feita à mão, recheio de bacon e gruyère firme e trêmulo no centro.',
		'author'      => $camille,
		'cozinhas'    => [ 'francesa' ],
		'porcoes'     => 8,
		'rendimento'  => 'fatias',
		'dificuldade' => 'dificil',
		'preparo'     => 40,
		'total'       => 115,
		'utensilios'  => "Tigela grande\nFrigideira\nForma de 24 cm com fundo removível\nRolo de massa\nFouet ou garfo\nPapel-manteiga e feijões crus (para pré-assar)",
		'ingredientes' => [
			i( 200, 'g', 'farinha de trigo', '', 'Massa' ),
			i( 100, 'g', 'manteiga', 'bem gelada, em cubos', 'Massa' ),
			i( 1, 'unidade', 'ovo', '', 'Massa' ),
			i( 1, 'pitada', 'sal', '', 'Massa' ),
			i( 30, 'ml', 'água gelada', '', 'Massa' ),
			i( 200, 'g', 'bacon', 'em cubos', 'Recheio' ),
			i( 3, 'unidade', 'ovos', '', 'Recheio' ),
			i( 200, 'g', 'creme de leite fresco', '', 'Recheio' ),
			i( 100, 'ml', 'leite', '', 'Recheio' ),
			i( 100, 'g', 'queijo gruyère', 'ralado', 'Recheio' ),
			i( null, 'a gosto', 'noz-moscada e pimenta', '', 'Recheio' ),
		],
		'passos' => [
			s( 'tigela', 'Faça a massa', 'Esfregue a manteiga na farinha com a ponta dos dedos até virar uma farofa. Junte ovo e água e una sem sovar.', [
				'ing'    => [ si( 200, 'g', 'farinha' ), si( 100, 'g', 'manteiga gelada' ), si( 1, 'pitada', 'sal' ), si( 1, 'unidade', 'ovo' ), si( 30, 'ml', 'água gelada' ) ],
				'pronto' => 'a massa se juntar quando você aperta',
				'dica'   => 'Trabalhe rápido: manteiga gelada é o segredo da massa crocante.',
			] ),
			s( 'geladeira', 'Gele a massa', 'Achate num disco e embale em filme plástico.', [ 'min' => 30 ] ),
			s( 'forno', 'Preaqueça o forno', 'Enquanto a massa gela.', [ 'temp' => 190 ] ),
			s( 'frigideira', 'Doure o bacon', 'Depois, escorra em papel-toalha.', [
				'ing'    => [ si( 200, 'g', 'bacon' ) ],
				'min'    => 6, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'estiver dourado e crocante',
			] ),
			s( 'bancada', 'Forre a forma', 'Abra a massa com 3 mm de espessura, forre a forma e fure o fundo com um garfo.' ),
			s( 'forno', 'Pré-asse a massa', 'Cubra com papel-manteiga e feijões crus para não estufar. Retire os feijões no fim.', [
				'min' => 15, 'temp' => 190,
				'pronto' => 'as bordas começarem a dourar',
			] ),
			s( 'tigela', 'Bata o recheio', 'Com o fouet. Tempere com noz-moscada e pimenta; pouco sal, o bacon já salga.', [
				'ing'    => [ si( 3, 'unidade', 'ovos' ), si( 200, 'g', 'creme de leite fresco' ), si( 100, 'ml', 'leite' ), si( 100, 'g', 'gruyère ralado' ) ],
				'pronto' => 'estiver homogêneo',
			] ),
			s( 'bancada', 'Monte a quiche', 'Espalhe o bacon sobre a massa e despeje o recheio devagar.' ),
			s( 'forno', 'Asse', '', [
				'min'     => 30, 'temp' => 180,
				'pronto'  => 'o centro estiver firme, mas ainda tremendo levemente ao balançar a forma',
				'atencao' => 'Use luvas: a forma e o recheio saem muito quentes.',
			] ),
			s( 'descanso', 'Descanse antes de cortar', 'Assim as fatias saem limpas.', [ 'min' => 10 ] ),
		],
	],

	/* 7 ------------------------------------------------------------------ */
	[
		'slug'        => 'chili-com-carne',
		'title'       => 'Chili com carne',
		'excerpt'     => 'Carne moída, feijão vermelho e especiarias tostadas. Picante na medida que você escolher.',
		'author'      => $team,
		'cozinhas'    => [ 'mexicana' ],
		'porcoes'     => 6,
		'dificuldade' => 'facil',
		'preparo'     => 20,
		'total'       => 55,
		'utensilios'  => "Panela média com tampa\nColher de pau",
		'ingredientes' => [
			i( 30, 'g', 'azeite' ),
			i( 1, 'unidade', 'cebola', 'picada' ),
			i( 2, 'dente', 'alho', 'picados' ),
			i( 1, 'unidade', 'pimenta jalapeño', 'sem sementes, picada fina' ),
			i( 1, 'unidade', 'pimentão vermelho', 'em cubos' ),
			i( 500, 'g', 'carne moída' ),
			i( 2, 'colher chá', 'cominho' ),
			i( 1, 'colher sopa', 'páprica defumada' ),
			i( 1, 'colher chá', 'orégano' ),
			i( 400, 'g', 'tomate pelado', 'amassado com as mãos' ),
			i( 400, 'g', 'feijão vermelho cozido', 'escorrido' ),
			i( 150, 'ml', 'água' ),
			i( 1, 'colher chá', 'sal' ),
			i( null, 'a gosto', 'coentro, limão e creme azedo', 'para servir' ),
		],
		'passos' => [
			s( 'panela', 'Refogue os vegetais', '', [
				'ing'     => [ si( 30, 'g', 'azeite' ), si( 1, 'unidade', 'cebola picada' ), si( 2, 'dente', 'alho picados' ), si( 1, 'unidade', 'jalapeño picada' ), si( 1, 'unidade', 'pimentão em cubos' ) ],
				'min'     => 5, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto'  => 'os vegetais estiverem macios',
				'atencao' => 'Depois de mexer na pimenta, lave as mãos antes de tocar o rosto.',
			] ),
			s( 'panela', 'Doure a carne com as especiarias', 'Solte a carne com a colher, sem deixar blocos.', [
				'ing'    => [ si( 500, 'g', 'carne moída' ), si( 2, 'colher chá', 'cominho' ), si( 1, 'colher sopa', 'páprica defumada' ), si( 1, 'colher chá', 'orégano' ) ],
				'min'    => 8, 'fogo' => 'alto', 'mexer' => 'vezes',
				'pronto' => 'não houver mais partes rosadas',
			] ),
			s( 'panela', 'Cozinhe com tomate e feijão', '', [
				'ing'    => [ si( 400, 'g', 'tomate pelado' ), si( 400, 'g', 'feijão vermelho' ), si( 150, 'ml', 'água' ), si( 1, 'colher chá', 'sal' ) ],
				'min'    => 25, 'fogo' => 'baixo', 'mexer' => 'vezes', 'tampa' => 'entreaberta',
				'pronto' => 'o molho estiver encorpado',
				'dica'   => 'Quer mais picante? Junte outra jalapeño picada nos últimos 5 minutos.',
			] ),
			s( 'descanso', 'Descanse', 'Com o fogo desligado.', [ 'min' => 5, 'pronto' => 'engrossar mais um pouco' ] ),
			s( 'servir', 'Sirva', 'Com coentro, gotas de limão e creme azedo. Vai bem com arroz ou tortilhas.' ),
		],
	],

	/* 8 ------------------------------------------------------------------ */
	[
		'slug'        => 'bobo-de-camarao',
		'title'       => 'Bobó de camarão',
		'excerpt'     => 'Creme de mandioca com leite de coco e dendê, camarão no ponto exato.',
		'author'      => $marina,
		'cozinhas'    => [ 'frutos-do-mar', 'nordestina' ],
		'porcoes'     => 6,
		'dificuldade' => 'media',
		'preparo'     => 30,
		'total'       => 60,
		'utensilios'  => "Liquidificador\nPanela grande com tampa\nTigela\nColher de pau",
		'ingredientes' => [
			i( 700, 'g', 'camarão médio', 'limpo', 'Camarão' ),
			i( 1, 'unidade', 'limão', 'espremido', 'Camarão' ),
			i( 1, 'colher chá', 'sal', '', 'Camarão' ),
			i( 600, 'g', 'mandioca cozida', 'sem o fio central', 'Creme' ),
			i( 400, 'ml', 'leite de coco', '', 'Creme' ),
			i( 300, 'ml', 'caldo de peixe', 'ou água', 'Creme' ),
			i( 1, 'unidade', 'cebola', 'picada', 'Refogado' ),
			i( 3, 'dente', 'alho', 'picados', 'Refogado' ),
			i( 1, 'unidade', 'pimentão vermelho', 'em cubos', 'Refogado' ),
			i( 2, 'unidade', 'tomates', 'sem sementes, picados', 'Refogado' ),
			i( 40, 'g', 'azeite de dendê', '', 'Refogado' ),
			i( 0.5, 'maço', 'coentro', 'picado', 'Refogado' ),
		],
		'passos' => [
			s( 'tigela', 'Tempere o camarão', 'Misture e leve à geladeira enquanto prepara o resto.', [
				'ing' => [ si( 700, 'g', 'camarão' ), si( 1, 'unidade', 'limão (suco)' ), si( 1, 'colher chá', 'sal' ) ],
			] ),
			s( 'liquidificador', 'Bata a mandioca com o leite de coco', '', [
				'ing'    => [ si( 600, 'g', 'mandioca cozida' ), si( 400, 'ml', 'leite de coco' ), si( 300, 'ml', 'caldo de peixe' ) ],
				'min'    => 1, 'vel' => 'alta',
				'pronto' => 'virar um creme liso, sem pedaços',
			] ),
			s( 'panela', 'Refogue no dendê', '', [
				'ing'    => [ si( 40, 'g', 'azeite de dendê' ), si( 1, 'unidade', 'cebola picada' ), si( 3, 'dente', 'alho picados' ), si( 1, 'unidade', 'pimentão em cubos' ), si( 2, 'unidade', 'tomates picados' ) ],
				'min'    => 5, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'o tomate começar a desmanchar',
			] ),
			s( 'panela', 'Cozinhe o creme', 'Despeje o creme do liquidificador na panela.', [
				'min'     => 10, 'fogo' => 'baixo', 'mexer' => 'sempre', 'tampa' => 'entreaberta',
				'pronto'  => 'engrossar e começar a soltar do fundo',
				'atencao' => 'O creme espirra quando ferve: mexa com a tampa entreaberta e use colher de cabo longo.',
			] ),
			s( 'panela', 'Junte o camarão', '', [
				'ing'    => [ si( 700, 'g', 'camarão temperado' ) ],
				'min'    => 5, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'os camarões ficarem rosados e curvados em “C”',
				'dica'   => 'Passou do “C” para o “O”, endureceu. Desligue assim que todos estiverem rosados.',
			] ),
			s( 'servir', 'Finalize com coentro', 'Sirva com arroz branco e farofa de dendê.', [
				'ing' => [ si( 0.5, 'maço', 'coentro picado' ) ],
			] ),
		],
	],

	/* 9 ------------------------------------------------------------------ */
	[
		'slug'        => 'creme-de-abobora-com-gengibre',
		'title'       => 'Creme de abóbora com gengibre',
		'excerpt'     => 'Aveludado, levemente picante e pronto em meia hora.',
		'author'      => $lorenzo,
		'cozinhas'    => [ 'sopas' ],
		'porcoes'     => 4,
		'dificuldade' => 'facil',
		'preparo'     => 15,
		'total'       => 45,
		'utensilios'  => "Panela média com tampa\nLiquidificador (ou mixer de mão)\nConcha\nPano de prato",
		'ingredientes' => [
			i( 20, 'g', 'azeite' ),
			i( 1, 'unidade', 'cebola', 'picada' ),
			i( 1, 'dente', 'alho', 'picado' ),
			i( 15, 'g', 'gengibre fresco', 'descascado e fatiado' ),
			i( 800, 'g', 'abóbora cabotiá', 'sem casca, em cubos' ),
			i( 600, 'ml', 'caldo de legumes' ),
			i( 1, 'colher chá', 'sal' ),
			i( 100, 'ml', 'leite de coco' ),
			i( null, 'a gosto', 'sementes de abóbora tostadas', 'para servir' ),
		],
		'passos' => [
			s( 'panela', 'Refogue cebola, alho e gengibre', '', [
				'ing'    => [ si( 20, 'g', 'azeite' ), si( 1, 'unidade', 'cebola picada' ), si( 1, 'dente', 'alho picado' ), si( 15, 'g', 'gengibre' ) ],
				'min'    => 3, 'fogo' => 'medio', 'mexer' => 'vezes',
				'pronto' => 'a cebola estiver transparente',
			] ),
			s( 'panela', 'Cozinhe a abóbora', '', [
				'ing'    => [ si( 800, 'g', 'abóbora em cubos' ), si( 600, 'ml', 'caldo de legumes' ), si( 1, 'colher chá', 'sal' ) ],
				'min'    => 20, 'fogo' => 'medio', 'mexer' => 'vezes', 'tampa' => 'com',
				'pronto' => 'a abóbora desmanchar ao toque do garfo',
			] ),
			s( 'descanso', 'Deixe amornar', 'Com o fogo desligado e a panela destampada.', [
				'min'     => 5,
				'atencao' => 'Líquido fervendo no liquidificador empurra a tampa. Espere amornar.',
			] ),
			s( 'liquidificador', 'Bata até ficar aveludado', 'Em duas levas, segurando a tampa com um pano de prato.', [
				'ing'    => [ si( 100, 'ml', 'leite de coco' ) ],
				'min'    => 1, 'vel' => 'alta',
				'pronto' => 'estiver liso e aveludado',
			] ),
			s( 'panela', 'Aqueça e acerte o sal', 'Volte o creme para a panela.', [
				'min'    => 2, 'fogo' => 'baixo', 'mexer' => 'vezes',
				'pronto' => 'voltar a fumegar',
			] ),
			s( 'servir', 'Sirva', 'Com um fio de azeite e sementes de abóbora tostadas.' ),
		],
	],

	/* 10 ----------------------------------------------------------------- */
	[
		'slug'        => 'brigadeiro-de-colher',
		'title'       => 'Brigadeiro de colher',
		'excerpt'     => 'Cremoso, com cacau de verdade e um ponto fácil de reconhecer.',
		'author'      => $camille,
		'cozinhas'    => [ 'sobremesas' ],
		'porcoes'     => 6,
		'rendimento'  => 'potinhos',
		'dificuldade' => 'facil',
		'preparo'     => 20,
		'total'       => 45,
		'utensilios'  => "Panela média de fundo grosso\nEspátula de silicone\nPotinhos",
		'ingredientes' => [
			i( 395, 'g', 'leite condensado', '1 lata' ),
			i( 30, 'g', 'cacau em pó 50%', 'peneirado' ),
			i( 20, 'g', 'manteiga' ),
			i( 1, 'pitada', 'sal' ),
			i( 200, 'g', 'creme de leite' ),
			i( null, 'a gosto', 'granulado ou raspas de chocolate', 'para decorar' ),
		],
		'passos' => [
			s( 'panela', 'Misture antes de ligar o fogo', 'Com a espátula, até o cacau dissolver por completo.', [
				'ing'    => [ si( 395, 'g', 'leite condensado' ), si( 30, 'g', 'cacau em pó' ), si( 20, 'g', 'manteiga' ), si( 1, 'pitada', 'sal' ) ],
				'pronto' => 'não houver grumos de cacau',
			] ),
			s( 'panela', 'Cozinhe mexendo', 'Raspe o fundo e as laterais o tempo todo.', [
				'min'     => 12, 'fogo' => 'baixo', 'mexer' => 'sempre',
				'pronto'  => 'ao passar a espátula, o fundo da panela aparecer por 2 segundos',
				'dica'    => 'Quer de enrolar? Cozinhe mais 3 minutos e pule o creme de leite.',
				'atencao' => 'Muito quente: não prove direto da panela.',
			] ),
			s( 'panela', 'Junte o creme de leite', '', [
				'ing'    => [ si( 200, 'g', 'creme de leite' ) ],
				'min'    => 1, 'fogo' => 'baixo', 'mexer' => 'sempre',
				'pronto' => 'estiver cremoso e brilhante',
			] ),
			s( 'descanso', 'Distribua e esfrie', 'Transfira para os potinhos.', [
				'min'    => 20,
				'pronto' => 'estiver morno e um pouco mais firme',
			] ),
			s( 'servir', 'Decore e sirva', 'Granulado ou raspas de chocolate por cima.' ),
		],
	],
];

global $wpdb;
$ids = [];
foreach ( $recipes as $n => $r ) {
	$existing = get_page_by_path( $r['slug'], OBJECT, 'receita' );
	$postarr  = [
		'post_type'    => 'receita',
		'post_status'  => 'publish',
		'post_title'   => $r['title'],
		'post_name'    => $r['slug'],
		'post_excerpt' => $r['excerpt'],
		'post_author'  => $r['author'],
		'post_date'    => wp_date( 'Y-m-d H:i:s', time() - ( count( $recipes ) - $n ) * HOUR_IN_SECONDS ),
	];
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
	}
	$id = wp_insert_post( $postarr, true );
	if ( is_wp_error( $id ) ) {
		cmx_cli( 'Erro: ' . $r['title'] . ' — ' . $id->get_error_message() );
		continue;
	}

	// Limpa linhas antigas dos repetidores (o modelo de passos pode ter mudado).
	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND (meta_key LIKE %s OR meta_key LIKE %s OR meta_key LIKE %s OR meta_key LIKE %s)",
		$id,
		'passos%',
		'\_passos%',
		'ingredientes%',
		'\_ingredientes%'
	) );
	wp_cache_delete( $id, 'post_meta' );

	wp_set_object_terms( $id, array_map( static fn( $slug ) => $term_ids[ $slug ], $r['cozinhas'] ), 'cozinha' );

	update_field( 'field_cmx_porcoes', $r['porcoes'], $id );
	update_field( 'field_cmx_rendimento', $r['rendimento'] ?? 'porções', $id );
	update_field( 'field_cmx_dificuldade', $r['dificuldade'], $id );
	update_field( 'field_cmx_tempo_preparo', $r['preparo'], $id );
	update_field( 'field_cmx_tempo_total', $r['total'], $id );
	update_field( 'field_cmx_utensilios', $r['utensilios'], $id );
	update_field( 'field_cmx_ingredientes', $r['ingredientes'], $id );
	update_field( 'field_cmx_passos', $r['passos'], $id );

	$ids[ $r['slug'] ] = $id;
	cmx_cli( sprintf( '  ✓ %s · %d passos', $r['title'], count( $r['passos'] ) ) );
}

/* -------------------------------------------------------------------------
 * Favoritos e seguidores de exemplo (assinante-demo)
 * ---------------------------------------------------------------------- */

$favorites = array_values( array_filter( [ $ids['risoto-de-cogumelos'] ?? 0, $ids['baiao-de-dois'] ?? 0, $ids['brigadeiro-de-colher'] ?? 0 ] ) );
update_user_meta( $member, 'cmx_favorites', $favorites );
update_user_meta( $member, 'cmx_following', [ $marina, $camille ] );

// Recalcula contadores a partir de todos os usuários (o script pode rodar várias vezes).
foreach ( $ids as $post_id ) {
	update_post_meta( $post_id, 'cmx_favorites', 0 );
}
foreach ( [ $marina, $lorenzo, $camille ] as $chef_id ) {
	update_user_meta( $chef_id, 'cmx_followers', 0 );
}
foreach ( get_users( [ 'fields' => 'ID' ] ) as $user_id ) {
	foreach ( cmx_user_favorites( (int) $user_id ) as $post_id ) {
		update_post_meta( $post_id, 'cmx_favorites', (int) get_post_meta( $post_id, 'cmx_favorites', true ) + 1 );
	}
	foreach ( cmx_user_following( (int) $user_id ) as $chef_id ) {
		update_user_meta( $chef_id, 'cmx_followers', cmx_followers_count( $chef_id ) + 1 );
	}
}

cmx_cli( 'Receitas: ' . count( $ids ) . '. Pronto!' );
