<?php
/**
 * Utilisations de pipelines par le plugin Identifiants
 *
 * @plugin     Identifiants
 * @copyright  2016
 * @author     tcharlss
 * @licence    GNU/GPL
 * @package    SPIP\Identifiants\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Ajouter du contenu sur les formulaires d'édition des objets.
 *
 * - Ajout de la saisie identifiant sur les objets configurés.
 *
 * @pipeline editer_contenu_objet
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function identifiants_editer_contenu_objet($flux) {

	// Identifiants sur les objets activés
	if (
		$objet = $flux['args']['type']
		and $table_objet = table_objet_sql($objet)
		and $tables_identifiables = identifiants_lister_tables_identifiables()
		and in_array($table_objet, $tables_identifiables)
		and include_spip('inc/autoriser')
		and autoriser('modifier', '_identifiant')
	) {

		// récupérer le squelette de la saisie
		// la valeur de l'identifiant est donnée dans formulaire_charger
		$saisie = recuperer_fond(
			'prive/objets/editer/identifiant',
			array(
				'identifiant' => $flux['args']['contexte']['identifiant'],
				'erreurs'     => $flux['args']['contexte']['erreurs'],
			)
		);

		// On insère la saisie après le titre si l'objet possède ce champ,
		// sinon après le premier champ (qu'on considère comme le titre),
		// sinon au niveau des champs extras.
		$cherche_titre = "/
			(
				<(?:div)[^>]*class=(?:'|\")editer\s+editer_titre[\s'\"]
				.*?
				<\/(?:div)>
			)
			\s*
			(<(?:div)[^>]*class=(?:'|\")editer)
			/isx";
		$cherche_1er_champ = "/
			(
				<(?:div)[^>]*?>
				\s*
				<(?:div)[^>]*class=(?:'|\")editer.*?<\/(?:div)>
			)
			\s*
			(<(?:div)[^>]*class=(?:'|\")editer)
			/isx";
		$cherche_extra = '%(<!--extra-->)%is';

		if (preg_match($cherche_titre, $flux['data'])) {
			$flux['data'] = preg_replace($cherche_titre, '$1'.$saisie.'$2', $flux['data'], 1);
		} elseif (preg_match($cherche_1er_champ, $flux['data'])) {
			$flux['data'] = preg_replace($cherche_1er_champ, '$1'.$saisie.'$2', $flux['data'], 1);
		} elseif (preg_match($cherche_extra, $flux['data'])) {
			$remplace_extra = "<div class='editer-groupe identifiant'>$saisie</div>\n" . '$1';
			$flux['data'] = preg_replace($cherche_extra, $remplace_extra, $flux['data'], 1);
		}
	}

	return $flux;
}


/**
 * Ajouter des vérifications aux formulaires
 *
 * - Vérifier le format et l'unicité de l'identifiant lors de l'édition d'un objet,
 * pour les objets configurés.
 *
 * @pipeline formulaire_verifier
 * @uses verifier_identifiant_dist()
 * @param array $flux Données du pipeline
 * @return array      Données du pipeline
 */
function identifiants_formulaire_verifier($flux) {

	if (
		$identifiant = _request('identifiant')
		// Vérifier s'il s'agit d'un formulaire `editer_xxx`, sans regex
		and substr($flux['args']['form'], 0, strpos($flux['args']['form'], '_')) === 'editer'
		// On suppose que l'objet est la partie `xxx` dans `editer_xxx`
		and $objet = substr($flux['args']['form'], strpos($flux['args']['form'], '_') + 1)
		// On suppose que l'id est le 1er paramètre (ça fait beaucoup de suppositions...)
		and $id_objet = $flux['args']['args'][0]
		and include_spip('base/objets')
		and $table_objet = table_objet_sql($objet)
		and $tables_identifiables = identifiants_lister_tables_identifiables()
		and in_array($table_objet, $tables_identifiables)
		and include_spip('inc/autoriser')
		and autoriser('modifier', '_identifiant')
	) {

		include_spip('inc/config');
		$unicite = !empty(lire_config('identifiants/unicite'));
		$options = array(
			'unicite'  => $unicite,
			'objet'    => $objet,
			'id_objet' => $id_objet,
		);
		// Nb : pas inc/verifier car le plugin n'est pas une dépendance obligatoire
		$verifier = charger_fonction('identifiant', 'verifier/');
		$erreur   = $verifier($identifiant, $options);

		if ($erreur) {
			$flux['data']['identifiant'] = $erreur;
		}
	}

	return $flux;
}


/**
 * Contrôler ou modifier les contenus postés juste avant l'enregistrement.
 *
 * - Ajout du champ identifiant si nécessaire.
 *
 * @note
 * On ne devrait pas avoir à utiliser ce pipeline,
 * mais certains objets mettent une liste d’inclusion en dur dans leur action de modification
 * au lieu de prendre la liste des champs éditables, du coup les champs ajoutés
 * dans `declarer_tables_objets_sql()` passent à la trappe.
 *
 * @see collecter_requests()
 *
 * @pipeline editer_contenu_objet
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function identifiants_pre_edition($flux) {

	// S'assurer d'avoir le champ `identifiant` dans le flux sur les objets activés
	if (
		$flux['args']['action'] === 'modifier'
		and !isset($flux['data']['identifiant'])
		and $table_objet = $flux['args']['spip_table_objet']
		and $tables_identifiables = identifiants_lister_tables_identifiables()
		and in_array($table_objet, $tables_identifiables)
		and _request('identifiant') !== null
		and include_spip('inc/autoriser')
		and autoriser('modifier', '_identifiant')
	) {
		$flux['data']['identifiant'] = _request('identifiant');
	}

	return $flux;
}


/**
 * Ajouter du contenu dans la boîte infos d'un objet
 *
 * - Affiche l'identifiant sous le n° de l'objet pour les objets configurés.
 *
 * @pipeline boite_infos
 * @param array $flux Données du pipeline
 * @return array      Données du pipeline
 */
function identifiants_boite_infos($flux) {

	if (
		$objet = $flux['args']['type']
		and $id_objet = intval($flux['args']['id'])
		and include_spip('base/objets')
		and $cle_objet = id_table_objet($objet)
		and $table_objet = table_objet_sql($objet)
		and $tables_identifiables = identifiants_lister_tables_identifiables()
		and in_array($table_objet, $tables_identifiables)
		and include_spip('inc/autoriser')
		and autoriser('voir', '_identifiant')
	) {

		// récupérer la valeur de l'identifiant
		$identifiant = sql_getfetsel(
			'identifiant',
			$table_objet,
			$cle_objet.'='.intval($id_objet)
		);

		// récupérer le squelette
		$info = recuperer_fond(
			'prive/objets/infos/identifiant',
			array(
				'identifiant' => $identifiant,
			)
		);

		$cherche = "/(<div[^>]*class=('|\")numero.*?<\/div>)/is";
		$remplace = '$1' . "$info\n";
		$flux['data'] = preg_replace($cherche, $remplace, $flux['data']);
	}

	return $flux;
}


/**
 * Ajouter du contenu dans la colonne de gauche d'un objet
 *
 * - Affiche la suggestion de création d'identifiants utiles.
 *
 * @pipeline affiche_droite
 * @param array $flux Données du pipeline
 * @return array      Données du pipeline
 */
function identifiants_affiche_droite($flux) {

	if (
		// Page d'un objet pas en édition
		isset($flux['args']['type-page'])
		and $exec = trouver_objet_exec($flux['args']['type-page'])
		and !$exec['edition']
		and $objet = $exec['type']
		and $table = $exec['table_objet_sql']
		and $cle_objet = $exec['id_table_objet']
		and isset($flux['args'][$cle_objet])
		and $id_objet = intval($flux['args'][$cle_objet])
		and include_spip('base/objets')
		and include_spip('inc/identifiants')
		// Fait partie des objets configures
		and $tables_identifiables = identifiants_lister_tables_identifiables()
		and in_array($table, $tables_identifiables)
		and include_spip('inc/autoriser')
		and autoriser('voir', '_identifiant')
	) {
		// Fait partie des objets ayants des identifiants utiles
		include_spip('identifiants_fonctions');
		include_spip('inc/config');
		$unicite = !empty(lire_config('identifiants/unicite'));
		if (_SPIP_VERSION_ID >= 41000) {
			$lang = generer_objet_info($id_objet, $objet, 'lang');
		} else {
			$lang = generer_info_entite($id_objet, $objet, 'lang');
		}
		$identifiants_utiles = identifiants_utiles($objet, $lang, $unicite);
		if ($identifiants_utiles) {
			// récupérer le squelette
			$utiles = recuperer_fond(
				'prive/squelettes/inclure/identifiants_utiles',
				array(
					'objet'               => $objet,
					'id_objet'            => $id_objet,
					'identifiants_utiles' => $identifiants_utiles,
				)
			);

			$flux['data'] .= $utiles;
		}
	}

	return $flux;
}
