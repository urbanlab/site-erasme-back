<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
$GLOBALS['url_arbo_parents']['article'] = [];

/**
 * Decoder une url propre
 * retrouve le fond et les parametres d'une URL abregee
 * le contexte deja existant est fourni dans args sous forme de tableau
 *
 * @param string $url
 * @param string $entite
 * @param array $contexte
 * @return array([contexte],[type],[url_redirect],[fond]) : url decodee
 */
function urls_propres_decoder_url(string $url, string $entite, array $contexte = []): array {
	if (ctype_digit($url)) {
		return [
			array_merge($contexte, ['id_article' => $url]),
			'article'
		];
	} else {
		return urls_propres_decoder_url_dist($url, $entite, $contexte);
	}
}


/**
 * Decoder une url arbo
 * retrouve le fond et les parametres d'une URL abregee
 * le contexte deja existant est fourni dans args sous forme de tableau
 *
 * @param string $url
 * @param string $entite
 * @param array $contexte
 * @return array([contexte],[type],[url_redirect],[fond]) : url decodee
 */
function urls_arbo_decoder_url(string $url, string $entite, array $contexte = []): array {
	if (ctype_digit($url)) {
		return [
			array_merge($contexte, ['id_article' => $url]),
			'article'
		];
	} else {
		return urls_arbo_decoder_url_dist($url, $entite, $contexte);
	}
}
