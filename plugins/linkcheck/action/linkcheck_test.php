<?php

function action_linkcheck_test_dist() {
	include_spip('inc/autoriser');
	include_spip('inc/linkcheck');

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$id_linkcheck = intval($arg);
	if (!$id_linkcheck) {
		spip_log("action_linkcheck_test_dist $arg pas compris");
		return;
	}

	linkcheck_tester_un_linkcheck($id_linkcheck, true);
}
