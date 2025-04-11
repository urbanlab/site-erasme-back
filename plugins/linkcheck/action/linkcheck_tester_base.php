<?php

function action_linkcheck_tester_base_dist() {
	include_spip('inc/autoriser');
	include_spip('inc/linkcheck');

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action(); // ne sert pas ici

	include_spip('inc/config');
	$timeout = time() + 20;
	$limit = 10;

	// par défaut on veut tester les linkchecks en etat inconnu
	$linkchecks = sql_allfetsel('*', 'spip_linkchecks', "etat=''", '', 'id_linkcheck ASC', "0,$limit");
	if (empty($linkchecks)) {
		// si il y en a plus, on va tester les plus anciens, et du coup ça finira jamais
		$linkchecks = sql_allfetsel('*', 'spip_linkchecks', "", '', 'maj DESC', "0,$limit");
	}
	foreach ($linkchecks as $linkcheck) {
		linkcheck_tester_un_linkcheck($linkcheck);
		if (time() > $timeout) {
			break;
		}
	}

	if (defined('_AJAX') && _AJAX) {
		include_spip('linkcheck_fonctions');
		$chiffres = linkcheck_chiffre();
		include_spip('inc/action');
		ajax_retour(json_encode($chiffres), 'application/json');
		exit;
	} else {
		if ($redirect = _request('redirect')) {
			$GLOBALS['redirect'] = parametre_url($redirect, 'message', 'check_ok', '&');
		}
	}
}
