<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pipeline utilisé pour le cas où les urls sont de type propres
 * dans ce cas on considère que l'url propre d'un article est le numéro de l'article
 * @param array $data;
 * @return array $data
**/
function era_propres_creer_chaine_url($data) {
	include_spip('action/editer_url');

	// url pour les articles
	if ($data['objet']['type'] == 'article') {
		$articles = sql_fetsel('*', 'spip_articles', 'id_article=' . $data['objet']['id_objet']);
		$secteur = sql_getfetsel('titre', 'spip_rubriques', 'id_rubrique='.$articles['id_secteur']);
		$data['data'] = url_nettoyer($secteur, _URLS_PROPRES_MAX, _URLS_PROPRES_MIN, '-', \_url_minuscules ? 'spip_strtolower' : '' ) . '/' . $articles['id_article'];
	}

	return $data;
}
