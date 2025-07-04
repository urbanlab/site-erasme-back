<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('action/editer_url');
/**
 * Génération rapide de l'url d'un article, sans même en base : retourner l'id
 * @param int $id_article,
 * @param string $args
 * @param string $ancre
 * @return string
**/
function urls_generer_url_article(int $id_article, $args = '', $ancre = ''): string {
	$articles = sql_fetsel('*', 'spip_articles', 'id_article=' . $id_article);
	$secteur = sql_getfetsel('titre', 'spip_rubriques', 'id_rubrique='.$articles['id_secteur']);
	$secteur = url_nettoyer($secteur, _URLS_PROPRES_MAX, _URLS_PROPRES_MIN, '-', \_url_minuscules ? 'spip_strtolower' : '' );
	return _DIR_RACINE . $secteur.'/'.$id_article . ($args ? "?$args" : '') . ($ancre ? "#$ancre" : '');
}
