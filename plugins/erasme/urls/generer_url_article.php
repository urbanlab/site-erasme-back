<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');
/**
 * Génération rapide de l'url d'un article, sans même en base : retourner l'id
 * @param int $id_article,
 * @param string $args
 * @param string $ancre
 * @return string
**/
function urls_generer_url_article(int $id_article, $args = '', $ancre = ''): string {
	return _DIR_RACINE . $id_article . ($args ? "?$args" : '') . ($ancre ? "#$ancre" : '');
}
