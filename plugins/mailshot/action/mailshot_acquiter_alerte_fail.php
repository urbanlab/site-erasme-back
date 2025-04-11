<?php
/**
 * Plugin MailShot
 * (c) 2012 Cedric Morin
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) return;



function action_mailshot_acquiter_alerte_fail_dist(){

	$securiser_action = charger_fonction("securiser_action","inc");
	$arg = $securiser_action();

	if (autoriser('acquiter', 'alertefail')) {
		include_spip('inc/mailshot');
		mailshot_fail_ratio_alert_acquit();
	}
}
