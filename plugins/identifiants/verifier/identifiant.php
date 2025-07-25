<?php
/**
 * API de vérification : vérification de la validité d'un identifiant
 *
 * @copyright  2019
 * @author     tcharlss
 * @licence    GNU/GPL
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifie la validité d'un identifiant
 *
 * @param string $valeur
 *   La valeur à vérifier.
 * @param array $options
 *   - normaliser (bool) : pour normaliser la valeur 
 *   - unicite (bool)    : pour vérifier l'unicité si c'est un objet
 *   - objet (string)    : type d'objet pour vérifier l'unicité
 *   - id_objet          : numéro d'un objet pour vérifier l'unicité
 * @param null $valeur_normalisee
 *   Si normalisation a faire, la variable sera remplie par la valeur normalisée.
 * @return string
 *   Retourne une chaîne vide si c'est valide, sinon une chaîne expliquant l'erreur.
 */
function verifier_identifiant_dist(string $identifiant, array $options = [], &$valeur_normalisee = null) {
	include_spip('inc/filtres');
	$erreur = '';

	// Longueur max
	$longueur_max = 255;
	$longueur = strlen($identifiant);
	if ($longueur > $longueur_max) {
		$erreur = _T(
			'identifiant:erreur_identifiant_taille',
			array('nb' => $longueur, 'nb_max' => $longueur_max)
		);
	}

	// Format : caractères alphanumériques en minuscules ou "_"
	$slug = identifiant_slug($identifiant, '', array('longueur_maxi' => $longueur_max));
	if (
		!$erreur
		and $slug !== $identifiant
	) {
		$erreur = _T('identifiant:erreur_identifiant_format', ['slug' => $slug]);
	}

	// Unicité : doit être unique pour ce type d'objet, sauf autres langues.
	if (
		!$erreur
		and !empty($options['unicite'])
		and !empty($options['objet'])
	) {
		include_spip('base/objets');
		$trouver_table = charger_fonction('trouver_table', 'base');
		$objet         = objet_type($options['objet']);
		$id_objet      = (int) (isset($options['id_objet']) ? $options['id_objet'] : 0);
		$table_objet   = table_objet_sql($objet);
		$cle_objet     = id_table_objet($objet);
		$desc          = $trouver_table($table_objet);
		$where = array(
			"$cle_objet != $id_objet",
			'identifiant=' . sql_quote($identifiant)
		);
		// l'identifiant a le droit d'être utilisé dans une autre langue
		if (isset($desc['field']['lang'])) {
			$lang    = sql_getfetsel('lang', $table_objet, $cle_objet . '=' . intval($id_objet));
			$where[] = 'lang='.sql_quote($lang);
		}
		$doublon = sql_getfetsel($cle_objet, $table_objet, $where);
		if ($doublon) {
			$erreur = _T('identifiant:erreur_identifiant_doublon_objet', array('objet' => $objet, 'id_objet' => $doublon));
		}
	}

	// S'il n'y a pas d'erreur, normaliser si demandé
	if (
		!$erreur
		and !empty($option['normaliser'])
	) {
		$valeur_normalisee = $slug;
	}

	return $erreur;
}
