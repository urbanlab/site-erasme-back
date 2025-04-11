<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

// @deprecated
// utiliser instituermailsubscriptions
function notifications_instituermailsubscription_dist($quoi, $id_mailsubscriber, $options) {

	// @deprecated : il faut normalement utiliser l'option notify de subscribe et unsubscribe
	// pour eviter l'envoi des notifications d'inscription/desincription
	if (isset($GLOBALS['notification_instituermailsubscriber_status']) and !$GLOBALS['notification_instituermailsubscriber_status']) {
		return;
	}

	$notifications = charger_fonction('notifications', 'inc');

	$options = [
		'subscriptions' =>
			[
				$options
			]
	];
	// on delegue a instituermailsubscriptions qui sait gerer plusieurs inscriptions d'un coup
	return $notifications('instituermailsubscriptions', $id_mailsubscriber, $options);
}
