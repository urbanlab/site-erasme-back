<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

/**
 * Actualiser les segments d'une liste
 *
 * @param string|int $id_mailsubscriber
 */
function action_actualiser_segments_mailsubscriber_dist($id_mailsubscriber = null) {

	if (is_null($id_mailsubscriber)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_mailsubscriber = $securiser_action();
	}

	include_spip('inc/mailsubscribinglists');

	// on force la mise a jour des segments pour ce subscriber
	// (pas besoin d'autorisations pour faire ca)
	mailsubscribers_actualise_segments($id_mailsubscriber, true);
}
