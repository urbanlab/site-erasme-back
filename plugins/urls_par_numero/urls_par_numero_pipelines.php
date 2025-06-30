<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('lire_config');

/**
 * Pipeline utilisé pour le cas où les urls sont de type propres
 * dans ce cas on considère que l'url propre d'un article est le numéro de l'article
 * @param array $data;
 * @return array $data
**/
function urls_par_numero_propres_creer_chaine_url(array $data): array {
	if (in_any($data['objet']['type'], lire_config('urls_par_numero/objets'))) {
		$id_objet = $data['objet']['id_objet'];
		$data['data'] = $id_objet;
		$data['objet']['url'] = $id_objet;
		settype($data['data'], 'string');
		settype($data['objet']['url'], 'string');
		// supprimer l'arborescence si c'est article
		if ($data['objet']['type'] === 'article') {
			unset($data['objet']['parent']);
			unset($data['objet']['id_parent']);
			unset($data['objet']['type_parent']);
		} else {//Préfixer dans le cas contraire
			$data['data'] = $data['objet']['type'] . '/' . $id_objet;
		}
	}
	return $data;
}

/**
 * Pipeline utilisé pour le cas où les urls sont de type aborescentes
 * dans ce cas on considère que l'url propre d'un article est le numéro de l'article
 * @param array $data;
 * @return array $data
**/
function urls_par_numero_arbo_creer_chaine_url(array $data): array {
	$data = urls_par_numero_propres_creer_chaine_url($data);
	return $data;
}
