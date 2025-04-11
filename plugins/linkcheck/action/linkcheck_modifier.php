<?php

function action_linkcheck_modifier_dist() {
	include_spip('inc/autoriser');
	include_spip('inc/linkcheck');
	include_spip('action/editer_objet');
	include_spip('inc/filtres');
	include_spip('inc/texte');

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$id_linkcheck = intval($arg);
	if (!$id_linkcheck) {
		spip_log("action_linkcheck_modifier_dist $arg pas compris");
		return;
	}

	$sel = sql_fetsel('*', 'spip_linkchecks', 'id_linkcheck = ' . intval($id_linkcheck));
	$search = $sel['url'];
	$replace = $sel['redirection'];

	$champs_exclus = ['extra', 'tables_liees', 'obligatoire', 'comite', 'minirezo', 'forum', 'mode', 'fichier', 'distant', 'media'];
	$liens = sql_allfetsel('*', 'spip_linkchecks_liens', 'id_linkcheck = ' . intval($id_linkcheck));
	foreach ($liens as $l) {

		$table = table_objet_sql($l['objet']);
		$trouver_table = charger_fonction('trouver_table', 'base');
		// trouver les champs de la vraie table
		$desc = $trouver_table($table);

		$champs = [];
		if (isset($desc['champs_editables']) and $desc['champs_editables']) {
			$champs = $desc['champs_editables'];
		} elseif (isset($desc['champs_versionnes'])) {
			$champs = $desc['champs_versionnes'];
		}
		// pas touche au champ extra serialize
		$champs = array_diff($champs, $champs_exclus);
		// que les champs qui existent
		$champs = array_intersect($champs, array_keys($desc['field']));
		// et qui sont en texte
		foreach ($champs as $c) {
			if (!preg_match(',text|varchar,', $desc['field'][$c])) {
				$champs = array_diff($champs, array($c));
			}
		}

		$primary = id_table_objet($table);
		$select = "$primary," . implode(',', $champs);

		$founds = [];
		$res = sql_select($select, $table, $primary . '=' . intval($l['id_objet']));
		while ($row = sql_fetch($res)) {
			$set = [];
			foreach ($champs as $c) {
				$nb = 0;
				$v = str_replace($search, $replace, $row[$c], $nb);

				if ($nb) {
					$set[$c] = $v;
					if (!isset($founds[$row[$primary]])) {
						$founds[$row[$primary]] = 0;
					}
					$founds[$row[$primary]] += $nb;
				}
			}
			if (count($set)) {
				objet_modifier($l['objet'], $row[$primary], $set);
			}
		}
	}

	sql_delete('spip_linkchecks', 'id_linkcheck = ' . intval($id_linkcheck));

	$id_linkcheck = sql_getfetsel('id_linkcheck', 'spip_linkchecks', '', '', 'id_linkcheck DESC', '0,1');
	linkcheck_tester_un_linkcheck($id_linkcheck, true);
}
