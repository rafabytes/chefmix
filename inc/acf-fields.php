<?php
/**
 * Campos do ACF registrados por código (não dependem de importação no banco).
 *
 * Receita → visão geral, ingredientes (mise en place) e passo a passo. Cada passo diz onde acontece
 * (panela, frigideira, tigela, liquidificador, forno…), com quais ingredientes, como regular
 * (fogo, forno, velocidade, mexer, tampa), por quanto tempo e como saber que está pronto.
 * Utensílios comuns de cozinha: nada pressupõe um aparelho específico.
 */
defined( 'ABSPATH' ) || exit;

/** Opções de unidade dos ingredientes (chave = como é gravado e exibido no singular). */
function cmx_units(): array {
	return [
		'g'           => 'g',
		'kg'          => 'kg',
		'ml'          => 'ml',
		'l'           => 'l',
		'xícara'      => 'xícara',
		'colher sopa' => 'colher (sopa)',
		'colher chá'  => 'colher (chá)',
		'unidade'     => 'unidade',
		'dente'       => 'dente',
		'fatia'       => 'fatia',
		'folha'       => 'folha',
		'ramo'        => 'ramo',
		'maço'        => 'maço',
		'lata'        => 'lata',
		'pitada'      => 'pitada',
		'a gosto'     => 'a gosto',
	];
}

/** Onde o passo acontece: o utensílio que a pessoa vai usar de verdade. */
function cmx_places(): array {
	return [
		'bancada'        => 'Bancada / tábua',
		'tigela'         => 'Tigela',
		'panela'         => 'Panela',
		'frigideira'     => 'Frigideira',
		'liquidificador' => 'Liquidificador',
		'processador'    => 'Processador / mixer',
		'batedeira'      => 'Batedeira',
		'forno'          => 'Forno',
		'geladeira'      => 'Geladeira',
		'descanso'       => 'Descanso',
		'servir'         => 'Servir',
	];
}

/** Locais que vão ao fogo. */
function cmx_places_heat(): array {
	return [ 'panela', 'frigideira' ];
}

/** Locais com motor (velocidade). */
function cmx_places_motor(): array {
	return [ 'liquidificador', 'processador', 'batedeira' ];
}

function cmx_flames(): array {
	return [
		'baixo' => 'Baixo',
		'medio' => 'Médio',
		'alto'  => 'Alto',
	];
}

function cmx_oven_temperatures(): array {
	$list = [];
	foreach ( [ 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 250 ] as $t ) {
		$list[ (string) $t ] = $t . ' °C';
	}
	return $list;
}

function cmx_speeds(): array {
	return [
		'pulsar' => 'Pulsar',
		'baixa'  => 'Baixa',
		'media'  => 'Média',
		'alta'   => 'Alta',
	];
}

function cmx_stirring(): array {
	return [
		'sempre' => 'Sem parar',
		'vezes'  => 'De vez em quando',
		'nao'    => 'Não mexa',
	];
}

function cmx_lids(): array {
	return [
		'com'         => 'Tampada',
		'entreaberta' => 'Entreaberta',
		'sem'         => 'Sem tampa',
	];
}

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$is_place = static function ( array $places ): array {
		return array_map( static fn( $p ) => [ [ 'field' => 'field_cmx_step_local', 'operator' => '==', 'value' => $p ] ], $places );
	};

	/* ---------------------------------------------------------------------
	 * Receita
	 * ------------------------------------------------------------------ */
	acf_add_local_field_group( [
		'key'             => 'group_cmx_receita',
		'title'           => 'Receita',
		'location'        => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'receita' ] ] ],
		'position'        => 'acf_after_title',
		'style'           => 'default',
		'label_placement' => 'top',
		'menu_order'      => 0,
		'fields'          => [
			[ 'key' => 'field_cmx_tab_geral', 'label' => 'Visão geral', 'type' => 'tab', 'placement' => 'top' ],
			[
				'key'           => 'field_cmx_porcoes',
				'label'         => 'Rende',
				'name'          => 'porcoes',
				'type'          => 'number',
				'default_value' => 4,
				'min'           => 1,
				'step'          => 1,
				'required'      => 1,
				'wrapper'       => [ 'width' => 15 ],
				'instructions'  => 'As quantidades são recalculadas quando a pessoa muda o rendimento.',
			],
			[
				'key'           => 'field_cmx_rendimento',
				'label'         => 'Unidade do rendimento',
				'name'          => 'rendimento_unidade',
				'type'          => 'text',
				'default_value' => 'porções',
				'placeholder'   => 'porções, unidades, fatias…',
				'wrapper'       => [ 'width' => 20 ],
			],
			[
				'key'           => 'field_cmx_dificuldade',
				'label'         => 'Dificuldade',
				'name'          => 'dificuldade',
				'type'          => 'button_group',
				'choices'       => cmx_difficulties(),
				'default_value' => 'facil',
				'wrapper'       => [ 'width' => 25 ],
			],
			[
				'key'          => 'field_cmx_tempo_preparo',
				'label'        => 'Tempo ativo (min)',
				'name'         => 'tempo_preparo',
				'type'         => 'number',
				'min'          => 0,
				'wrapper'      => [ 'width' => 20 ],
				'instructions' => 'Com a mão na massa, incluindo a mise en place.',
			],
			[
				'key'          => 'field_cmx_tempo_total',
				'label'        => 'Tempo total (min)',
				'name'         => 'tempo_total',
				'type'         => 'number',
				'min'          => 0,
				'wrapper'      => [ 'width' => 20 ],
				'instructions' => 'Vazio = soma dos tempos dos passos.',
			],
			[
				'key'          => 'field_cmx_utensilios',
				'label'        => 'Utensílios',
				'name'         => 'utensilios',
				'type'         => 'textarea',
				'rows'         => 3,
				'new_lines'    => '',
				'instructions' => 'Um por linha, com o tamanho quando importar. Ex.: Panela média com tampa · Frigideira grande · Liquidificador · Forma de 24 cm',
			],

			[ 'key' => 'field_cmx_tab_ingredientes', 'label' => 'Ingredientes (mise en place)', 'type' => 'tab' ],
			[
				'key'     => 'field_cmx_mise_guia',
				'label'   => 'Mise en place',
				'name'    => '',
				'type'    => 'message',
				'message' => 'É a primeira etapa do modo cozinhar: a pessoa separa e prepara tudo antes de ligar o fogo. Use <strong>Preparo</strong> para dizer o que fazer com cada ingrediente antes de começar (“picada fina”, “em cubos de 2 cm”, “em temperatura ambiente”). Assim os passos ficam curtos e ninguém precisa picar cebola com a panela no fogo.',
			],
			[
				'key'          => 'field_cmx_ingredientes',
				'label'        => 'Ingredientes',
				'name'         => 'ingredientes',
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => 'Adicionar ingrediente',
				'instructions' => 'Use "Grupo" para separar partes da receita (ex.: Massa, Recheio). Deixe a quantidade vazia para "a gosto".',
				'sub_fields'   => [
					[ 'key' => 'field_cmx_ing_grupo', 'label' => 'Grupo', 'name' => 'grupo', 'type' => 'text', 'wrapper' => [ 'width' => 16 ] ],
					[ 'key' => 'field_cmx_ing_qtd', 'label' => 'Qtd.', 'name' => 'quantidade', 'type' => 'number', 'step' => 'any', 'min' => 0, 'wrapper' => [ 'width' => 10 ] ],
					[ 'key' => 'field_cmx_ing_unidade', 'label' => 'Unidade', 'name' => 'unidade', 'type' => 'select', 'choices' => cmx_units(), 'allow_null' => 1, 'ui' => 0, 'wrapper' => [ 'width' => 16 ] ],
					[ 'key' => 'field_cmx_ing_nome', 'label' => 'Ingrediente', 'name' => 'nome', 'type' => 'text', 'required' => 1, 'wrapper' => [ 'width' => 30 ] ],
					[ 'key' => 'field_cmx_ing_obs', 'label' => 'Preparo', 'name' => 'obs', 'type' => 'text', 'placeholder' => 'picada fina, em cubos…', 'wrapper' => [ 'width' => 28 ] ],
				],
			],

			[ 'key' => 'field_cmx_tab_passos', 'label' => 'Passo a passo', 'type' => 'tab' ],
			[
				'key'     => 'field_cmx_passos_guia',
				'label'   => 'Como escrever um bom passo',
				'name'    => '',
				'type'    => 'message',
				'message' => '<strong>Um passo = uma ação</strong>, começando pelo verbo (“Refogue”, “Bata”, “Asse”). Diga <strong>onde</strong> acontece, coloque as quantidades em <em>Ingredientes deste passo</em> (elas acompanham o rendimento escolhido), regule fogo/forno/velocidade nos campos próprios e sempre preencha <strong>Pronto quando…</strong>: é o que diz à pessoa que ela pode avançar.',
			],
			[
				'key'          => 'field_cmx_passos',
				'label'        => 'Passos',
				'name'         => 'passos',
				'type'         => 'repeater',
				'layout'       => 'block',
				'collapsed'    => 'field_cmx_step_acao',
				'button_label' => 'Adicionar passo',
				'min'          => 1,
				'sub_fields'   => [
					[
						'key'           => 'field_cmx_step_local',
						'label'         => 'Onde acontece',
						'name'          => 'local',
						'type'          => 'button_group',
						'choices'       => cmx_places(),
						'default_value' => 'panela',
					],
					[
						'key'         => 'field_cmx_step_acao',
						'label'       => 'O que fazer (curto)',
						'name'        => 'acao',
						'type'        => 'text',
						'required'    => 1,
						'maxlength'   => 70,
						'placeholder' => 'Refogue a cebola',
					],
					[
						'key'       => 'field_cmx_step_instrucao',
						'label'     => 'Detalhes',
						'name'      => 'instrucao',
						'type'      => 'textarea',
						'rows'      => 2,
						'new_lines' => '',
					],
					[
						'key'          => 'field_cmx_step_ingredientes',
						'label'        => 'Ingredientes deste passo',
						'name'         => 'ingredientes',
						'type'         => 'repeater',
						'layout'       => 'table',
						'button_label' => 'Adicionar ingrediente ao passo',
						'sub_fields'   => [
							[ 'key' => 'field_cmx_si_qtd', 'label' => 'Qtd.', 'name' => 'quantidade', 'type' => 'number', 'step' => 'any', 'min' => 0, 'wrapper' => [ 'width' => 18 ] ],
							[ 'key' => 'field_cmx_si_unidade', 'label' => 'Unidade', 'name' => 'unidade', 'type' => 'select', 'choices' => cmx_units(), 'allow_null' => 1, 'wrapper' => [ 'width' => 24 ] ],
							[ 'key' => 'field_cmx_si_nome', 'label' => 'Ingrediente', 'name' => 'nome', 'type' => 'text', 'required' => 1 ],
						],
					],
					[
						'key'     => 'field_cmx_step_min',
						'label'   => 'Tempo (min)',
						'name'    => 'tempo_min',
						'type'    => 'number',
						'min'     => 0,
						'wrapper' => [ 'width' => 16 ],
					],
					[
						'key'     => 'field_cmx_step_seg',
						'label'   => 'Tempo (seg)',
						'name'    => 'tempo_seg',
						'type'    => 'number',
						'min'     => 0,
						'max'     => 59,
						'wrapper' => [ 'width' => 16 ],
					],
					[
						'key'               => 'field_cmx_step_fogo',
						'label'             => 'Fogo',
						'name'              => 'fogo',
						'type'              => 'button_group',
						'choices'           => cmx_flames(),
						'allow_null'        => 1,
						'wrapper'           => [ 'width' => 34 ],
						'conditional_logic' => $is_place( cmx_places_heat() ),
					],
					[
						'key'               => 'field_cmx_step_temp',
						'label'             => 'Temperatura do forno',
						'name'              => 'temperatura',
						'type'              => 'select',
						'choices'           => cmx_oven_temperatures(),
						'allow_null'        => 1,
						'wrapper'           => [ 'width' => 34 ],
						'conditional_logic' => $is_place( [ 'forno' ] ),
					],
					[
						'key'               => 'field_cmx_step_vel',
						'label'             => 'Velocidade',
						'name'              => 'velocidade',
						'type'              => 'button_group',
						'choices'           => cmx_speeds(),
						'allow_null'        => 1,
						'wrapper'           => [ 'width' => 34 ],
						'conditional_logic' => $is_place( cmx_places_motor() ),
					],
					[
						'key'               => 'field_cmx_step_mexer',
						'label'             => 'Mexer',
						'name'              => 'mexer',
						'type'              => 'button_group',
						'choices'           => cmx_stirring(),
						'allow_null'        => 1,
						'wrapper'           => [ 'width' => 50 ],
						'conditional_logic' => $is_place( cmx_places_heat() ),
					],
					[
						'key'               => 'field_cmx_step_tampa',
						'label'             => 'Tampa',
						'name'              => 'tampa',
						'type'              => 'button_group',
						'choices'           => cmx_lids(),
						'allow_null'        => 1,
						'wrapper'           => [ 'width' => 50 ],
						'conditional_logic' => $is_place( cmx_places_heat() ),
					],
					[
						'key'          => 'field_cmx_step_pronto',
						'label'        => 'Pronto quando…',
						'name'         => 'pronto',
						'type'         => 'text',
						'placeholder'  => 'a cebola estiver transparente',
						'instructions' => 'O sinal de que pode avançar (cor, textura, cheiro, ponto). Complete a frase “Pronto quando…”.',
					],
					[
						'key'         => 'field_cmx_step_dica',
						'label'       => 'Dica',
						'name'        => 'dica',
						'type'        => 'text',
						'placeholder' => 'Ex.: não mexa nos primeiros 2 minutos, é assim que doura.',
						'wrapper'     => [ 'width' => 50 ],
					],
					[
						'key'         => 'field_cmx_step_atencao',
						'label'       => 'Atenção (segurança)',
						'name'        => 'atencao',
						'type'        => 'text',
						'placeholder' => 'Ex.: a gordura respinga; afaste o rosto.',
						'wrapper'     => [ 'width' => 50 ],
					],
					[
						'key'           => 'field_cmx_step_imagem',
						'label'         => 'Foto do passo (opcional)',
						'name'          => 'imagem',
						'type'          => 'image',
						'return_format' => 'id',
						'preview_size'  => 'thumbnail',
						'library'       => 'all',
					],
				],
			],
		],
	] );

	/* ---------------------------------------------------------------------
	 * Perfil do chef
	 * ------------------------------------------------------------------ */
	acf_add_local_field_group( [
		'key'      => 'group_cmx_chef',
		'title'    => 'Perfil público do chef',
		'location' => [ [ [ 'param' => 'user_role', 'operator' => '==', 'value' => 'chef' ] ] ],
		'fields'   => [
			[
				'key'           => 'field_cmx_chef_foto',
				'label'         => 'Foto',
				'name'          => 'foto',
				'type'          => 'image',
				'return_format' => 'id',
				'preview_size'  => 'thumbnail',
				'library'       => 'uploadedTo',
				'instructions'  => 'Quadrada, de preferência com fundo claro.',
			],
			[
				'key'         => 'field_cmx_chef_especialidade',
				'label'       => 'Especialidade',
				'name'        => 'especialidade',
				'type'        => 'text',
				'placeholder' => 'Cozinha nordestina contemporânea',
			],
			[
				'key'         => 'field_cmx_chef_cidade',
				'label'       => 'Cidade',
				'name'        => 'cidade',
				'type'        => 'text',
				'placeholder' => 'Recife, PE',
				'wrapper'     => [ 'width' => 50 ],
			],
			[
				'key'     => 'field_cmx_chef_instagram',
				'label'   => 'Instagram',
				'name'    => 'instagram',
				'type'    => 'text',
				'prepend' => '@',
				'wrapper' => [ 'width' => 50 ],
			],
			[
				'key'     => 'field_cmx_chef_bio_aviso',
				'label'   => 'Biografia',
				'name'    => '',
				'type'    => 'message',
				'message' => 'A biografia exibida no perfil é o campo <strong>Informações biográficas</strong>, acima.',
			],
		],
	] );

	/* ---------------------------------------------------------------------
	 * Cozinha (cores e ícone da capa)
	 * ------------------------------------------------------------------ */
	acf_add_local_field_group( [
		'key'      => 'group_cmx_cozinha',
		'title'    => 'Aparência',
		'location' => [ [ [ 'param' => 'taxonomy', 'operator' => '==', 'value' => 'cozinha' ] ] ],
		'fields'   => [
			[
				'key'          => 'field_cmx_cozinha_tom',
				'label'        => 'Cor',
				'name'         => 'tom',
				'type'         => 'select',
				'choices'      => wp_list_pluck( cmx_tones(), 'label' ),
				'allow_null'   => 1,
				'instructions' => 'Usada nas capas das receitas sem foto.',
			],
			[
				'key'        => 'field_cmx_cozinha_icone',
				'label'      => 'Ícone',
				'name'       => 'icone',
				'type'       => 'select',
				'choices'    => cmx_cuisine_icons(),
				'allow_null' => 1,
			],
		],
	] );

	/* ---------------------------------------------------------------------
	 * Configurações
	 * ------------------------------------------------------------------ */
	if ( function_exists( 'acf_add_options_sub_page' ) ) {
		acf_add_options_sub_page( [
			'page_title'  => 'ChefMix — Configurações',
			'menu_title'  => 'ChefMix',
			'menu_slug'   => 'chefmix-settings',
			'parent_slug' => 'options-general.php',
			'capability'  => 'manage_options',
		] );
	}

	acf_add_local_field_group( [
		'key'      => 'group_cmx_settings',
		'title'    => 'Login e cadastro',
		'location' => [ [ [ 'param' => 'options_page', 'operator' => '==', 'value' => 'chefmix-settings' ] ] ],
		'fields'   => [
			[
				'key'          => 'field_cmx_google_client_id',
				'label'        => 'Google — Client ID',
				'name'         => 'google_client_id',
				'type'         => 'text',
				'placeholder'  => '1234567890-abc.apps.googleusercontent.com',
				'instructions' => 'Google Cloud Console → APIs e serviços → Credenciais → ID do cliente OAuth (Aplicativo da Web). Em <em>Origens JavaScript autorizadas</em>, cadastre a origem do site (ex.: <code>https://rafabytes.com</code>). Não é preciso URI de redirecionamento nem chave secreta: o botão do Google entrega um token assinado que o tema confere. Também aceita a constante <code>CHEFMIX_GOOGLE_CLIENT_ID</code> no wp-config.php.',
			],
			[
				'key'           => 'field_cmx_cadastro_email',
				'label'         => 'Permitir cadastro com e-mail e senha',
				'name'          => 'cadastro_email',
				'type'          => 'true_false',
				'ui'            => 1,
				'default_value' => 1,
			],
		],
	] );
} );
