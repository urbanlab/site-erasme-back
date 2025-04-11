<?php

/**
 * Plugin LinkCheck
 * (c) 2013 Benjamin Grapeloux, Guillaume Wauquier, Guillaume Wauquier
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Un fichier de fonctions permet de definir des elements
 * systematiquement charges lors du calcul des squelettes.
 *
 * Il peut par exemple définir des filtres, critères, balises, …
 *
 */
function linkcheck_en_url($url, $distant = null) {
	$inc_lien = charger_fonction('lien', 'inc');

	$retour = false;
	if (is_null($distant)) {
		$distant = (strpos($url, '://') === false ? false : true);
	}
	if (strlen($url) > 0) {
		if ($distant == 0) {
			$lien = $inc_lien($url);
			$titre = supprimer_tags($lien);
			if ($titre !== $url) {
				$titre = ' (' . couper($titre, 60) . ')';
			} else {
				$titre = '';
			}
			$retour = "<a href=\"$url\" title=\""
				. attribut_html(_T('linkcheck:ouvrenouvelonglet'))
				."\" target=\"_blank\">$url{$titre}</a>";
		} else {
			$retour = "<a href=\"$url\" class==\"spip_out\" rel=\"external\" title=\""
				. attribut_html(_T('linkcheck:ouvrenouvelonglet'))
				."\" target=\"_blank\">$url</a>";
		}
	}
	return $retour;
}

function balise_LINKCHECK_CHIFFRE($p) {
	$p->code = 'linkcheck_chiffre()';
	return $p;
}

function linkcheck_chiffre() {
	$tab_chiffre = [];
	$tab_chiffre['nb_lien'] = sql_getfetsel('count(id_linkcheck)', 'spip_linkchecks');
	if ($tab_chiffre['nb_lien'] > 0) {
		$tab_chiffre['nb_lien_inconnu'] = sql_getfetsel('count(id_linkcheck)', 'spip_linkchecks', 'etat=\'\'');
		foreach (['mort', 'malade', 'restreint', 'deplace', 'ok'] as $etat) {
			$tab_chiffre['nb_lien_' . $etat] = sql_getfetsel('count(id_linkcheck)', 'spip_linkchecks', 'etat=' . sql_quote($etat));
			$tab_chiffre['pct_lien_' . $etat] = $tab_chiffre['nb_lien_' . $etat] * 100 / $tab_chiffre['nb_lien'];
		}
	}
	$tab_chiffre['parcours_progress'] = 0;
	$do = lire_config('linkcheck_dernier_objet', '');
	$dio = lire_config('linkcheck_dernier_id_objet', 0);
	$done = 0;
	$total = 0;
	// repartir de la table ou on en était
	include_spip('inc/linkcheck');
	$tables_a_traiter = linkcheck_tables_a_traiter();
	$liste_tables = array_keys($tables_a_traiter);
	while (!empty($liste_tables)) {
		$table_sql = array_shift($liste_tables);
		$primary = id_table_objet($table_sql);
		$nb = sql_countsel($table_sql);
		$total += $nb;
		if ($table_sql === $do) {
			$done += sql_countsel($table_sql, "$primary<=".intval($dio));
			$do = '';
		} elseif ($do) {
			$done += $nb;
		}
	}
	if ($done > 0) {
		$tab_chiffre['parcours_progress'] = round($done / $total * 100, 3);
		// s'assurer qu'on est pas à zero, pour distinguer le "on a commencé" de "on a pas du tout commencé"
		$tab_chiffre['parcours_progress'] = max($tab_chiffre['parcours_progress'], 0.01);
	}
	$tab_chiffre['parcours_objets_total'] = $total;
	$tab_chiffre['parcours_objets_done'] = $done;
	if (lire_config('linkcheck_etat_parcours', '')) {
		$tab_chiffre['parcours_progress'] = 100;
	}

	return $tab_chiffre;
}
