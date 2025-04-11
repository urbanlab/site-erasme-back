<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Tester si un objet doit etre verifié ou non selon son statut
 * On ne comptabilise les liens que sur les objets prévisualisables
 * on vérifie si le statut de l'objet est prévisualisable
 * @param $objet
 * @param $id_objet
 * @return bool
 */
function linkcheck_objet_verifiable($objet, $id_objet) {
	$table_sql = table_objet_sql($objet);
	$primary = id_table_objet($objet);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_sql);

	if (!empty($desc['statut'])) {
		$statut = reset($desc['statut']);
		$condition_previsu = $statut['previsu'] ?? $statut['publie'];
		$condition_previsu = str_replace("/auteur", "", $condition_previsu);
		// on veut un where en previsu, on file donc cette condition sur les 2 options
		include_spip('public/quete');
		$where = [
			"$primary=" . intval($id_objet)
		];
		$where[] = quete_condition_statut($statut['champ'] ?? 'statut', $condition_previsu, $condition_previsu, '', true);
		if (sql_countsel($table_sql, $where)) {
			$objet_verifiable = true;
		} else {
			$objet_verifiable = false;
		}
	} else {
		$objet_verifiable = true;
	}

	return $objet_verifiable;
}


/**
 * Verifier un objet/id_objet
 * - recenser ses liens
 * - les ajouter en base
 * @param string $objet
 * @param int $id_objet
 * @return array
 */
function linkcheck_objet_verifier(string $objet, int $id_objet, $checklink_async = true) {
	$primary = id_table_objet($objet);
	$table_sql = table_objet_sql($objet);
	$tables_a_traiter = linkcheck_tables_a_traiter();

	spip_log("linkcheck_objet_verifier: $objet #$id_objet", 'linkcheck' . _LOG_DEBUG);

	$champs = sql_fetsel('*', $table_sql, "$primary=" . intval($id_objet));
	if (
		empty($tables_a_traiter[$table_sql])
		|| empty($champs)
		|| !linkcheck_objet_verifiable($objet, $id_objet)
	) {
		spip_log("linkcheck_objet_verifier: $objet #$id_objet pas a traiter", 'linkcheck' . _LOG_DEBUG);
		$id_linkchecks = sql_allfetsel(
			'id_linkcheck',
			'spip_linkchecks_liens',
			'id_objet=' . intval($id_objet) . ' AND objet=' . sql_quote($objet)
		);
		if (!empty($id_linkchecks)) {
			spip_log("linkcheck_objet_verifier: $objet #$id_objet pas a traiter, on supprime ses vieux liens recensés", 'linkcheck' . _LOG_DEBUG);
			$id_linkchecks = array_column($id_linkchecks, 'id_linkcheck');
			sql_delete('spip_linkchecks_liens', [
				'id_objet=' . intval($id_objet) . ' AND objet=' . sql_quote($objet),
				sql_in('id_linkcheck', $id_linkchecks)
			]);
			// on ne supprime pas les linkchecks orphelins immediatement, ce sera fait en cron optimiser
		}
		return [];
	}

	$liens = linkcheck_recenser_liens($objet, $champs);
	spip_log("linkcheck_objet_verifier: $objet #$id_objet analysé ". count($liens)." liens trouvés", 'linkcheck' . _LOG_DEBUG);
	//on les insere dans la base
	$id_linkchecks = linkcheck_objet_ajouter_liens($objet, $id_objet, $liens);
	spip_log("linkcheck_objet_verifier: $objet #$id_objet analysé ". count($id_linkchecks)." liens en base", 'linkcheck' . _LOG_DEBUG);

	if ($checklink_async and !empty($id_linkchecks)) {
		spip_log("linkcheck_objet_verifier: $objet #$id_objet analysé, on lance linkcheck_objet_tester_liens en async", 'linkcheck' . _LOG_DEBUG);
		job_queue_add(
			'linkcheck_objet_tester_liens',
			'Tests des liens d\'un objet',
			[$objet, $id_objet],
			'inc/linkcheck',
			true
		);
	}

	return $id_linkchecks;
}

/**
 * @param string $objet
 * @param int $id_objet
 * @param ?array $id_linkchecks
 * @return array
 */
function linkcheck_objet_tester_liens($objet, $id_objet, $id_linkchecks = null) {

	if (is_null($id_linkchecks)) {
		$id_linkchecks = sql_allfetsel('id_linkcheck', 'spip_linkchecks_liens', ["objet=".sql_quote($objet).' AND id_objet='.intval($id_objet)]);
		$id_linkchecks = array_column($id_linkchecks, 'id_linkcheck');
	}

	$res = [];
	if (!empty($id_linkchecks)) {
		$linkchecks = sql_allfetsel('*', 'spip_linkchecks', sql_in('id_linkcheck', $id_linkchecks));
		foreach ($linkchecks as $linkcheck) {
			$test = linkcheck_tester_un_linkcheck($linkcheck);
			$test['id_linkcheck'] = $linkcheck['id_linkcheck'];
			$test['url'] = $linkcheck['url'];
			$res[] = $test;
		}
	}

	return $res;
}

/**
 * Fonction qui recherche la presence d'un lien et de sa liaison dans la table spip_linkchecks
 *
 * @param string $url
 * 	URL que l'on recherche
 * @param int $id_objet
 *   Identifiant de l'objet attaché
 * @param string $objet
 *   Type de l'objet attaché
 * @return array
 *   tableau associatif avec une clé 'etat' 3 valeur possible
 *   - 0 : le lien n'a pas été trouvé
 *   - 1 : le lien a été trouvé mais il n'est pas rattaché à l'objet
 * 	 - 2 : le lien a été trouvé et il est bien rattaché à l'objet
 * 	 Si la clé "etat" est égale à 1, le tableau indique par la clé "id" l'identifiant du lien
 */
function linkcheck_tester_presence_lien($url, $id_objet, $objet, $publie) {
	$retour = ['etat' => 0];
	// on recherche le lien par l'URL
	$id_linkcheck = sql_getfetsel('id_linkcheck', 'spip_linkchecks', 'url = ' . sql_quote($url));
	if ($id_linkcheck) {
		// si on l'a trouvé on verifie si est attaché à l'objet passé en paramatre
		$sel = sql_fetsel(
			'id_linkcheck, publie',
			'spip_linkchecks_liens',
			'id_linkcheck=' . $id_linkcheck . ' AND id_objet=' . intval($id_objet) . ' AND objet=' . sql_quote($objet)
		);
		if (!$sel) {
			$retour['etat'] = 1;
			$retour['id'] = $id_linkcheck;
		} else {
			$retour['etat'] = 2;
			$retour['id'] = $id_linkcheck;
			if ($publie != $sel['publie']) {
				sql_updateq(
					'spip_linkchecks_liens',
					['publie' => $publie],
					'id_linkcheck=' . $id_linkcheck . ' AND id_objet=' . intval($id_objet) . ' AND objet=' . sql_quote($objet)
				);
				$statut_linkckeck = sql_getfetsel('publie', 'spip_linkchecks', 'id_linkcheck = ' . intval($id_linkcheck));
				if (!$statut_linkckeck or $publie != $statut_linkckeck) {
					if (!$statut_linkckeck or $statut_linkckeck == '' or $publie == 'oui') {
						sql_updateq(
							'spip_linkchecks',
							['publie' => $publie],
							'id_linkcheck=' . intval($id_linkcheck)
						);
					} elseif (!sql_getfetsel('publie', 'spip_linkchecks_liens', 'id_linkcheck=' . $id_linkcheck . '  AND publie="oui"')) {
						sql_updateq(
							'spip_linkchecks',
							['publie' => $publie],
							'id_linkcheck=' . intval($id_linkcheck)
						);
					}
				}
			}
		}
	}
	return $retour;
}


/**
 * Fonction qui ajoute les liens dans la base
 *
 * @param string $type_objet
 * 		Type de l'objet à lier
 * @param int $id_objet
 * 		Identifiant de l'objet à lier
 * @param array $liens
 *        Tableau de liens à ajouter
 *
 * @return array $id_linkchecks
 *      liste des id_linkcheck correspondant aux liens ajoutés (ou déja existants)
 */
function linkcheck_objet_ajouter_liens($objet, $id_objet, $liens, $publie = null) {
	if (is_null($publie)) {
		// On regarde si l'objet parent est publie
		$objet_publie = objet_test_si_publie($objet, $id_objet);

		if ($objet_publie) {
			$publie = 'oui';
		} else {
			$publie = 'non';
		}
	}

	$id_linkchecks = [];
	foreach ($liens as $lien) {
		// on teste si c'est un lien interne ou externe
		$distant = (strpos($lien, '.')) ? true : false;
		// on test son existence dans la base
		$exi = linkcheck_tester_presence_lien($lien, $id_objet, $objet, $publie);
		//s'il existe
		if ($exi['etat'] > 0 && !empty($exi['id'])) {
			if ($exi['etat'] == 1) {
				//on l'ajoute ds la table de liaison
				$ins = sql_insertq(
					'spip_linkchecks_liens',
					[
						'id_linkcheck' => $exi['id'],
						'id_objet' => $id_objet,
						'objet' => $objet,
						'publie' => $publie
					]
				);
				$publie_linkcheck = sql_getfetsel('publie', 'spip_linkchecks', 'id_linkcheck = ' . intval($exi['id']));
				if ($publie_linkcheck != $publie) {
					if ($publie_linkcheck == 'non') {
						sql_updateq('spip_linkchecks', ['publie' => 'oui'], 'id_linkcheck = ' . intval($exi['id']));
					} else {
						$ok = sql_countsel('spip_linkchecks_liens', 'publie = "oui" AND id_linkcheck = ' . intval($exi['id']));
						if (!$ok or $ok == 0) {
							sql_updateq('spip_linkchecks', ['publie' => 'non'], 'id_linkcheck = ' . intval($exi['id']));
						}
					}
				}
			}
			$id_linkchecks[] = $exi['id'];
		//s'il existe pas
		} else {
			//on l'insere dans la base des url
			$id_linkcheck = sql_insertq(
				'spip_linkchecks',
				['url' => $lien, 'distant' => $distant, 'date' => date('Y-m-d H:i:s'), 'publie' => $publie]
			);
			$id_linkchecks[] = $id_linkcheck;
			//et ds la base qui lie un url a un objet
			sql_insertq(
				'spip_linkchecks_liens',
				$donnees = [
					'id_linkcheck' => $id_linkcheck,
					'id_objet' => $id_objet,
					'objet' => $objet,
					'publie' => $publie
				]
			);
		}
	}

	spip_log("linkcheck_objet_ajouter_liens: $objet #$id_objet ajout/modif en base des linkchecks #" . implode(', #', $id_linkchecks), 'linkcheck' . _LOG_DEBUG);

	// il faut retirer les liens de cet objet vers des URLs qu'il ne contient plus (elles seront nettoyées par la suite)
	$anciens_liens = sql_allfetsel('id_linkcheck', 'spip_linkchecks_liens', ["id_objet=" . intval($id_objet), 'objet=' . sql_quote($objet), sql_in('id_linkcheck', $id_linkchecks, 'NOT')]);
	if (!empty($anciens_liens)) {
		$anciens_liens = array_column($anciens_liens, 'id_linkcheck');
		sql_delete('spip_linkchecks_liens', ['id_objet=' . intval($id_objet), 'objet=' .sql_quote($objet), sql_in('id_linkcheck', $anciens_liens)]);
		spip_log("linkcheck_objet_ajouter_liens: $objet #$id_objet suppression des liens vers les anciennes URLs #" . implode(', #', $anciens_liens), 'linkcheck' . _LOG_INFO_IMPORTANTE);
	}

	return $id_linkchecks;
}
