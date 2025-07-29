<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pipeline qui ajoute des taches automatiques
 *
 * @param array $taches
 */
function era_taches_generales_cron($taches) {
	$taches['generer_slug'] = 300; // toutes les 5 minutes on regarde si il y a des sites à tester
	return $taches;
}

function era_post_edition($flux) {
	// Cas des rubriques : création
	if (isset($flux['args']['table']) && $flux['args']['table'] === 'spip_articles'
		&& isset($flux['args']['action']) && $flux['args']['action'] === 'modifier'
		&& isset($flux['args']['id_objet'])
	) {
		$article = sql_fetsel('*', $flux['args']['table'], 'id_article=' . intval($flux['args']['id_objet']));
		sql_updateq($flux['args']['table'], ['identifiant' => identifiant_slug($article['titre'])], 'id_article=' . intval($flux['args']['id_objet']));
	}
}