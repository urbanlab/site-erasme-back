<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/objets');
define('_GRAPHQL_OBJETS_NON_CONFIGURABLES', []);

function formulaires_configurer_collections_saisies_dist() {
	$collections_non = array_merge(_GRAPHQL_OBJETS_NON_CONFIGURABLES, ['forums', 'depots', 'paquets', 'plugins']);
	$saisies = [];
	$tables = lister_tables_objets_sql();
	ksort($tables);
	foreach ($tables as $table => $infos) {
		$collection = $infos['table_objet'];
		$champ_id = $infos['key']['PRIMARY KEY'];
		if (sql_countsel($table) == '0') {
			array_push($collections_non, $collection);
		}

		if (!in_array($collection, $collections_non)) {
			$champs = [];
			foreach ($infos["field"] as $nom_champ => $def) {
				if (
					$nom_champ != $champ_id && !in_array($nom_champ, array_merge(GRAPHQL_CHAMPS_COMMUNS, ['nom', 'bio', 'id_vignette', 'nom_site']))
				) {
					$champs[$nom_champ] = $nom_champ;
				}
			}

			// On n'affiche que les tables auxiliaires finissant par "_liens"
			// et qui ont du contenu lié
			$collections_liees = [];
			foreach (lister_tables_auxiliaires() as $table_aux => $infos_aux) {
				if (
					preg_match("#^spip_(\w+)_liens$#", $table_aux, $matches) &&
					sql_countsel($table_aux, "objet='" . objet_type($table) . "'") != 0
				) {
					$collections_liees[$matches[1]] = $matches[1];
				}
			}

			$saisies_fieldset = [
				[
					'saisie' => 'checkbox',
					'options' => [
						'nom' => $collection . '_exposer',
						'conteneur_class' => 'pleine_largeur',
						'data' => [
							'actif' => _T('graphql:actif_oui'),
						],
					],
				],
				[
					'saisie' => 'input',
					'options' => [
						'nom' => $collection . '_pagination',
						'conteneur_class' => 'pleine_largeur',
						'explication' => _T('graphql:pagination'),
						'explication_apres' => _T('graphql:desc_arg_pagination'),
						'type' => 'number',
						'defaut' => '10',
						'afficher_si' => '@' . $collection . '_exposer@=="actif"',
					],
				],
				[
					'saisie' => 'selection_multiple',
					'options' => [
						'nom' => $collection . '_champs',
						'conteneur_class' => 'pleine_largeur',
						'explication' => _T('graphql:champs_objet'),
						'afficher_si' => '@' . $collection . '_exposer@=="actif"',
						'data' => $champs,
						'multiple' => 'oui',
						"cacher_option_intro" => "oui",
						'size' => 5,
					],
				],
			];

			if (!empty($collections_liees)) {
				$saisies_fieldset[] = [
					'saisie' => 'selection_multiple',
					'options' => [
						'nom' => $collection . '_liaisons',
						'conteneur_class' => 'pleine_largeur',
						'explication' => _T('graphql:cfg_collections_liees'),
						'afficher_si' => '@' . $collection . '_exposer@=="actif"',
						'data' => $collections_liees,
						'multiple' => 'oui',
						'option_intro' => _T('graphql:aucune'),
						'size' => 5,
					],
				];
			}

			$saisies_fieldset = array_merge($saisies_fieldset, [
				[
					'saisie' => 'select_all',
					'options' => [
						'nom' => $collection . '_select_all',
						'conteneur_class' => 'pleine_largeur',
						'afficher_si' => '@' . $collection . '_exposer@=="actif"',
					],
				],
				[
					'saisie' => 'submit',
					'options' => [
						'nom' => $collection . '_submit',
						'conteneur_class' => 'pleine_largeur',
					],
				],
			]);

			$saisies[] = [
				'saisie' => 'deplier_collection',
				'options' => [
					'nom' => $collection . '_deplier',
					'conteneur_class' => 'pleine_largeur',
					'collection' => $collection,
					'estActif' => empty(lire_config('/meta_graphql/objets_editoriaux/' . $collection)) ? "non" : "oui"
				],
			];

			$saisies[] = [
				'saisie' => 'fieldset',
				'options' => [
					'nom' => $collection,
					'conteneur_class' => 'config_' . $collection
				],
				'saisies' => $saisies_fieldset,
			];
		}
	}

	return $saisies;
}

function formulaires_configurer_collections_charger_dist() {
	$valeurs = [];
	$objets_editoriaux = lire_config('/meta_graphql/objets_editoriaux', array());

	foreach ($objets_editoriaux as $collection => $statut) {
		$valeurs[$collection . "_exposer"] = "actif";
		$valeurs[$collection . "_pagination"] = $statut["pagination"];
		$valeurs[$collection . "_champs"] = $statut["champs"];
		$valeurs[$collection . "_liaisons"] = $statut["liaisons"];
	}
	return $valeurs;
}

function formulaires_configurer_collections_traiter_dist() {
	$ret = [];
	$objets_editoriaux = [];
	foreach (lister_tables_objets_sql() as $table => $infos) {
		$collection = $infos['table_objet'];
		if (_request($collection . '_exposer')) {
			$objets_editoriaux[$collection] = [
				'pagination' => _request($collection . '_pagination'),
				'champs' => _request($collection . '_champs') ? _request($collection . '_champs') : [],
				'liaisons' => _request($collection . '_liaisons') ? _request($collection . '_liaisons') : [],
			];
		}
	}

	if (ecrire_config('/meta_graphql/objets_editoriaux', $objets_editoriaux)) {
		$ret['message_ok'] = _T('config_info_enregistree');
	} else {
		$ret['message_erreur'] = _T('erreur_technique_enregistrement_impossible');
	}

	return $ret;
}
