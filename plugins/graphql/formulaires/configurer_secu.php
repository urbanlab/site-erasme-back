<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_secu_saisies_dist() {
	$saisies = [
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'autoriser_get',
				'label' => _T('graphql:autoriser_get'),
				'explication' => _T('graphql:autoriser_get_desc'),
				'data' => [
					'oui' => _T('graphql:autoriser'),
				]
			]
		],
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'desactiver_schema',
				'label' => _T('graphql:schema_disabled'),
				'explication' => _T('graphql:schema_disabled_desc'),
				'data' => [
					'oui' => _T('graphql:desactiver'),
				]
			]
		],
		[
			'saisie' => 'input',
			'options' => [
				'nom' => 'profondeur_limit',
				'label' => _T('graphql:profondeur_limit'),
				'explication' => _T('graphql:profondeur_limit_desc'),
				'type' => 'number',
				'defaut' => '0',
				'min' => 0,
			],
		],
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'api_key_active',
				'label' => _T('graphql:activer_api_key'),
				'data' => [
					'oui' => _T('graphql:activer'),
				],
			],
		],
		[
			'saisie' => 'input',
			'options' => [
				'nom' => "jeton",
				'label' => _T('graphql:titre_configurer_jeton'),
				'readonly' => 'oui',
				'afficher_si' => '@api_key_active@ == "oui"',
				'afficher_si_avec_post' => 'oui',
			],
		]
	];

	return $saisies;
}

function formulaires_configurer_secu_charger_dist() {
	$valeurs = lire_config('/meta_graphql/securite', []);

	if (!array_key_exists('jeton', $valeurs)) {
		$valeurs["api_key_active"] = "non";
	} else {
		$valeurs["api_key_active"] = "oui";
	}

	return $valeurs;
}

function formulaires_configurer_secu_traiter_dist() {
	$choix = _request("api_key_active") ? _request("api_key_active") : "";
	$jeton = ($choix) ? _request("jeton") : null;
	$schema = _request("desactiver_schema") ? _request("desactiver_schema") : null;
	$profondeur_limit = _request("profondeur_limit") ? _request("profondeur_limit") : null;
	$autoriser_get = _request("autoriser_get") ? _request("autoriser_get") : null;

	$valeurs = null;
	if ($autoriser_get) $valeurs['autoriser_get'] = $autoriser_get;
	if ($profondeur_limit) $valeurs['profondeur_limit'] = $profondeur_limit;
	if ($schema) $valeurs['desactiver_schema'] = $schema;
	if ($jeton) $valeurs['jeton'] = $jeton;

	$ret = [];
	if (ecrire_config('/meta_graphql/securite', $valeurs)) {
		$ret['message_ok'] = _T('config_info_enregistree');
	} else {
		$ret['message_erreur'] = _T('erreur_technique_enregistrement_impossible');
	}

	return $ret;
}


function graphql_generate_key() {
	$d = time() * 1000;

	if (function_exists('hrtime')) {
		$hrtime = hrtime();
		$d += round($hrtime[1] / 1000000);
	} elseif (function_exists('microtime')) {
		$microtime = microtime(true);
		$d += round($microtime * 1000);
	}

	$uuid = sprintf('%08s-%04s-4%03x-%04x-%012s', substr($d, -8), substr($d, -12, 4), mt_rand(0, 0xfff), mt_rand(0, 0x3fff) | 0x8000, bin2hex(random_bytes(6)));

	return $uuid;
}
