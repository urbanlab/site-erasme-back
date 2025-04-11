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
 * Forcer la synchronisation de toutes les listes
 *
 */
function action_mailsubscribers_synchro_lists_dist() {
	// on prend la langue la plus probable pour le visiteur du moment qu'elle soit dans $GLOBALS['meta']['langues_multilingue']
	// on affecte $_COOKIE pour que l'appel suivant par minipres ne change rien ensuite
	$_COOKIE['spip_lang'] = utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue']);

	if (!autoriser('creer', 'mailsubscribinglist')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	include_spip('inc/mailsubscribers');
	$arg = _request('arg');

	$listes = null;
	if ($arg and ($arg != 'all')) {
		$listes = mailsubscribers_listes(['id' => $arg]);
	}

	mailsubscribers_synchro_lists($listes);
}
