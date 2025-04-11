<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/linkcheck_editer');
include_spip('inc/linkcheck_parser');
include_spip('inc/linkcheck_sonder');

/**
 * Detecter pour une liste de `$champs`,
 * si chacun est de type texte (1) (à parser pour trouver les URL) ou URL directement exploitable (0)
 * @param array $champs
 * @param array $desc
 * @return array
 */
function linkcheck_detecter_type_champs($champs, $desc) {
	$champs = array_flip($champs);
	foreach ($champs as $champ => &$type) {
		$type = 1;
		if (!empty($desc['field'][$champ])
		&& preg_match(',^(tiny|long|medium)?text\s?,i', $desc['field'][$champ])
		&& stripos($champ, 'url') !== false) {
			$type = 0;
		}
	}
	return $champs;
}

/**
 * Lister les tables à traiter par linkcheck avec la liste des champs et leur type pour chaque
 * @return array
 */
function linkcheck_tables_a_traiter() {
	static $tables;
	if (is_null($tables)) {
		$tables_config_linkcheck = lire_config('linkcheck/linkcheck_objets', ['spip_articles']);
		$tables_spip = lister_tables_objets_sql();
		$tables = [];
		if (!empty($tables_spip)) {
			foreach ($tables_spip as $table_sql => $infos) {
				if (!in_array($table_sql, $tables_config_linkcheck)) {
					continue;
				}
				// si il y a une declaration linkcheck_champs c'est elle qui compte,
				// on attends
				// * soit false / []
				// * soit la string '*' (pour dire 'tous les champs')
				// * soit un tableau associatif qui indique le type du champ (1 pour texte à parser, 0 pour une URL)
				// ['texte' => 1, 'url_site' => 0]
				if (isset($infos['linkcheck_champs'])) {
					// si elle est vide, il faut ignorer la table, sinon on check les champs listés ici
					if (!empty($infos['linkcheck_champs'])) {
						if (is_array($infos['linkcheck_champs'])) {
							$tables[$table_sql] = $infos['linkcheck_champs'];
						} elseif ($infos['linkcheck_champs'] === '*') {
							// la valeur spéciale * est remplacée par la liste complète de tous les champs déclarés
							$tables[$table_sql] = linkcheck_detecter_type_champs(array_keys($infos['field']), $infos);
						}
					}
				} elseif (!empty($infos['rechercher_champs'])) {
					// sinon on prend la liste des champs utilisés pour la recherche
					$tables[$table_sql] = linkcheck_detecter_type_champs(array_keys($infos['rechercher_champs']), $infos);
				}
			}
		}
	}
	return $tables;
}

/**
 * Association d'un etat de lien avec le premier chiffre des codes de statut http (0)
 * et avec le statut d'un objet (1)
 */
function linkcheck_etats_liens($status = null) {
	$status_to_etat = [
		0 => [
			'1' => 'malade',
			'2' => 'ok',
			'3' => 'deplace',
			'4' => 'mort',
			'5' => 'malade',
			'401' => 'restreint',
			'403' => 'restreint',
			'429' => 'malade',
		],
		1 => [
			'publie' => 'ok',
			'prepa' => 'malade',
			'prop' => 'malade',
			'refuse' => 'malade',
			'poubelle' => 'mort'
		]
	];
	if(!is_null($status)) {
		if (is_numeric($status)) {
			if (isset($status_to_etat[0][$status])) {
				return $status_to_etat[0][$status];
			}
			$first = substr(trim($status), 0, 1);
			return $status_to_etat[0][$first] ?? 'malade';
		} else {
			return $status_to_etat[1][$status] ?? 'malade';
		}
	}
	return $status_to_etat;
}

function linkcheck_purger() {
	include_spip('base/abstract_sql');
	include_spip('base/create');
	sql_drop_table('spip_linkchecks_liens');
	sql_drop_table('spip_linkchecks');
	maj_tables(['spip_linkchecks', 'spip_linkchecks_liens']);

	include_spip('inc/config');
	ecrire_config('linkcheck_etat_parcours', '');
	ecrire_config('linkcheck_dernier_objet', '');
	ecrire_config('linkcheck_dernier_id_objet', '');
}
