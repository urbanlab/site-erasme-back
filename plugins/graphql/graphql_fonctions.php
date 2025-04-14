<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function graphql_verif_jeton($headers) {
	$token_param = lire_config('/meta_graphql/securite/jeton', '');
	if ($token_param == '') {
		return true;
	}

	$token_envoye = '';
	if (isset($headers["X_AUTH_TOKEN"])) {
		$token_envoye = $headers["X_AUTH_TOKEN"];
	}

	return ($token_envoye == $token_param);
}

function graphql_getCollectionInfos(string $collection): array {
	// articles
	$infos['collection'] = $collection;
	// spip_articles
	$infos['table_collection'] = table_objet_sql($collection);
	// infos SQL de la table
	$infos['table_infos'] = lister_tables_objets_sql($infos['table_collection']);
	// Champs SQL de la table
	$infos['champs'] = $infos['table_infos']['field'];
	// id_article
	$infos['champ_id'] = id_table_objet($infos['table_collection']);
	// article
	$infos['type_objet'] = objet_type($collection);
	// Article
	$infos['nameObjet'] = ucfirst($infos['type_objet']);

	return $infos;
}
