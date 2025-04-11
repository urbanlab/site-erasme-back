<?php

/**
 * Plugin mailsubscribers
 * (c) 2017 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Exporter la base au format CSV
 *
 * @param null|string $arg
 */
function action_mailsubscribers_export_dist($arg = null) {
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// on prend la langue la plus probable pour le visiteur du moment qu'elle soit dans $GLOBALS['meta']['langues_multilingue']
	// on affecte $_COOKIE pour que l'appel suivant par minipres ne change rien ensuite
	$_COOKIE['spip_lang'] = utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue']);

	if (!autoriser('exporter', '_mailsubscribers')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$args = explode('-', $arg);
	$statut = $args[0];
	$id_liste = isset($args[1]) ? intval($args[1]) : false;
	$id_segment = isset($args[2]) ? intval($args[2]) : 0;
	$where = [];

	$entetes = [
		'email',
		'nom',
		'lang',
		'date',
		'statut',
		'listes',
	];

	$titre = _T('mailsubscriber:mailsubscribers_' . $statut);

	$exporter_csv = charger_fonction('exporter_csv', 'inc');

	$options = [
		'delim' => ',',
		'entetes' => $entetes,
	];

	$listes = sql_get_select('group_concat(L.identifiant)', 'spip_mailsubscriptions as S JOIN spip_mailsubscribinglists as L ON L.id_mailsubscribinglist=S.id_mailsubscribinglist', 'S.id_segment= ' . $id_segment . ' AND S.id_mailsubscriber=M.id_mailsubscriber');
	$listes = "($listes)";
	// fix les sous-requetes en select avec un prefixe non spip_
	if (!empty($GLOBALS['connexions'][0]['prefixe']) and $GLOBALS['connexions'][0]['prefixe'] !== 'spip') {
		// regardons si on a une version moderne de exporter_csv qui supporte les callback
		// dans ce cas on va renseigner les listes via la callback
		$refl = new ReflectionFunction($exporter_csv);
		$refs = $refl->getParameters();
		if (count($refs) === 3) {
			$listes = 'M.id_mailsubscriber';
			$options['callback'] = 'mailsubscribers_export_renseigner_ligne_listes';
		}
	}


	// si un id_liste est present, restreindre l'export à cette liste
	if ($id_liste) {
		$identifiant = sql_getfetsel('identifiant', 'spip_mailsubscribinglists', 'id_mailsubscribinglist=' . intval($id_liste));
		$titre .= '-' . $GLOBALS['meta']['nom_site'] . '-' . $identifiant . '-' . date('Y-m-d');
		$where[] = "N.id_mailsubscribinglist=$id_liste";
		// '' ou 'all' pour tout exporter (sauf poubelle)
		if (in_array($statut, ['', 'all'])) {
			$where[] = 'N.statut<>' . sql_quote('poubelle');
		} else {
			$where[] = 'N.statut=' . sql_quote($statut);
		}
		if ($id_segment > 0) {
			$where[] = 'N.id_segment=' . intval($id_segment);
		}
		$res = sql_select(
			"M.email,M.nom,M.lang,M.date,N.statut,$listes as listes",
			'spip_mailsubscribers AS M LEFT JOIN spip_mailsubscriptions as N ON M.id_mailsubscriber=N.id_mailsubscriber',
			$where
		);
	} else {
		// '' ou 'all' pour tout exporter (sauf poubelle)
		if (in_array($statut, ['', 'all'])) {
			$where[] = 'M.statut<>' . sql_quote('poubelle');
		} else {
			$where[] = 'M.statut=' . sql_quote($statut);
		}
		$titre .= '-' . $GLOBALS['meta']['nom_site'] . '-' . date('Y-m-d');
		$res = sql_select(
			"M.email,M.nom,M.lang,M.date,M.statut,$listes as listes",
			'spip_mailsubscribers AS M',
			$where
		);
	}

	if (empty($options['callback'])) {
		// vieille signature de exporter_csv, pour compat anciennes versions SPIP 3.2 ou bonux
		$exporter_csv($titre, $res, $options['delim'], $options['entetes']);
	}
	else {
		$exporter_csv($titre, $res, $options);
	}
}

/**
 * Callback appelee sur chaque ligne
 * @param $nb
 * @param $ligne
 * @param $importer_charset
 * @return mixed
 */
function mailsubscribers_export_renseigner_ligne_listes($nb, $ligne, $importer_charset) {
	if ($nb > 0) {
		$id_mailsubscriber = $ligne['listes'];
		$ligne['listes'] = sql_getfetsel('group_concat(L.identifiant)', 'spip_mailsubscriptions as S JOIN spip_mailsubscribinglists as L ON L.id_mailsubscribinglist=S.id_mailsubscribinglist', 'S.id_segment=0 AND S.id_mailsubscriber=' . intval($id_mailsubscriber));
	}
	return $ligne;
}
