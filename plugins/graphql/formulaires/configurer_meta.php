<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/objets');

function formulaires_configurer_meta_saisies_dist() {
	$saisies = [];
	$saisies[] = [
		'saisie' => 'checkbox',
		'options' => [
			'nom' => 'meta',
			'label' => _T('graphql:options_spip'),
			'data' => [
				'email_webmaster' => _T('email_webmaster'),
				'nom_site' => _T('nom_site'),
				'slogan_site' => _T('slogan_site'),
				'adresse_site' => _T('adresse_site'),
				'descriptif_site' => _T('descriptif_site'),
			],
		],
	];
	return $saisies;
}

function formulaires_configurer_meta_charger_dist() {
	$valeurs = [];
	$meta = lire_config('/meta_graphql/meta', "");

	$valeurs["meta"] = $meta;

	return $valeurs;
}

function formulaires_configurer_meta_traiter_dist() {
	$ret = [];
	$metas = is_null(_request('meta')) ? [] : _request('meta');

	if (ecrire_config('/meta_graphql/meta', $metas)) {
		$ret['message_ok'] = _T('config_info_enregistree');
	} else {
		$ret['message_erreur'] = _T('erreur_technique_enregistrement_impossible');
	}

	return $ret;
}
