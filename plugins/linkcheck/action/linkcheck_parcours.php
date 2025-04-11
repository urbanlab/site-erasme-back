<?php

function action_linkcheck_parcours_dist() {
	set_time_limit(0);
	include_spip('inc/autoriser');

	include_spip('inc/linkcheck');
	include_spip('inc/autoriser');
	include_spip('inc/queue');
	include_spip('inc/config');


	if (autoriser('webmestre')) {
		$id_branche = _request('branche', 0);
		$fini = linkcheck_parcourir($id_branche, time() + 20);
		if (!$fini) {
			if ($redirect = _request('redirect')) {
				$GLOBALS['redirect'] = parametre_url($redirect, 'message', 'parcours_in_progress');
			}
		} else {
			if ($redirect = _request('redirect')) {
				$GLOBALS['redirect'] = parametre_url($redirect, 'message', 'parcours_ok');
			}
		}
	}
}
