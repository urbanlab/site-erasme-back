<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

/**
 * Ne pas consentir à l'option et se desabonner
 *
 * @param string $email
 * @param array $id_mailsubscribinglists
 * @param bool $double_optin
 */
function action_unconsent_mailsubscriber_dist($email = null, $id_mailsubscribinglists = null, $double_optin = null) {
	include_spip('mailsubscribers_fonctions');
	include_spip('inc/mailsubscribers');

	if (is_null($email)) {
		$arg = mailsubscribers_verifier_args_action('unconsent');
		if ($arg) {
			list($email, $id_mailsubscribinglists) = $arg;
		}
		$double_optin = true;
	} else {
		if (is_null($double_optin)) {
			$double_optin = false;
		}
	}

	$unsubscribe_mailsubscriber = charger_fonction('unsubscribe_mailsubscriber', 'action');
	$unsubscribe_mailsubscriber($email, $id_mailsubscribinglists, $double_optin, true);
}
