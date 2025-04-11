<?php

function action_linkcheck_reinit_dist() {
	include_spip('inc/autoriser');
	include_spip('inc/config');
	include_spip('inc/linkcheck');

	if (autoriser('reinitialiser', 'linkcheck')) {
		linkcheck_purger();
	}

	if ($redirect = _request('redirect')) {
		$GLOBALS['redirect'] = parametre_url($redirect, 'message', 'delete_ok');
	}
}
