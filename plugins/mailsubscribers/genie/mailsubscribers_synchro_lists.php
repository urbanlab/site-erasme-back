<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}


/**
 * CRON de synchro des listes
 * @param $t
 * @return int
 */
function genie_mailsubscribers_synchro_lists_dist($t) {

	include_spip('inc/mailsubscribers');
	mailsubscribers_synchro_lists();

	// les prepa et prop de plus d'1 mois d'anciennete passent a la poubelle
	sql_updateq(
		'spip_mailsubscribers',
		['statut' => 'poubelle'],
		sql_in('statut', ['prepa', 'prop']) . ' AND date<' . sql_quote(date('Y-m-d H:i:s', strtotime('-1 month')))
	);

	return 1;
}
