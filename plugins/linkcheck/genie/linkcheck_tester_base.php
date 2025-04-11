<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function genie_linkcheck_tester_base_dist($t) {
	$tests = [
		'' => [
			'limit' => 30,
			'order' => 'id_linkcheck'
		],
		'ok' => [
			'limit' => 20,
			'periode' => 7 * 24 * 3600,
		],
		'restreint' => [
			'limit' => 10,
			'periode' => 14 * 24 * 3600,
		],
		'deplace' => [
			'limit' => 10,
			'periode' => 2 * 24 * 3600,
		],
		'malade' => [
			'limit' => 10,
			'periode' => 2 * 24 * 3600,
		],
		'mort' => [
			'limit' => 10,
			'periode' => 30 * 24 * 3600,
		],
	];


	include_spip('inc/linkcheck');
	$encore = false;
	// on s'accorde 20s pour rester dans le timeout
	$timeout = time() + 20;
	foreach ($tests as $etat => $config) {
		$limit = $config['limit'] ?? 30;
		$where = [
			'etat=' . sql_quote($etat),
		];
		if (!empty($config['periode'])) {
			$where[] = "maj < '" . date('Y-m-d H:i:s', time() - $config['periode']) . "'";
		}
		// par defaut on prend ceux qui ont été controlés le plus anciennement en premier
		$order = $config['order'] ?? 'maj';

		$linkchecks = sql_allfetsel('*', 'spip_linkchecks', $where, '', $order, '0,' . strval($limit + 1));
		if (count($linkchecks) > $limit) {
			$encore = true;
			array_pop($linkchecks);
		}
		spip_log("genie_linkcheck_tester_base_dist: verifier " . count($linkchecks) ." URLS en etat '$etat'", 'linkcheck' . _LOG_DEBUG);
		foreach ($linkchecks as $linkcheck) {
			linkcheck_tester_un_linkcheck($linkcheck);
			if (time() > $timeout) {
				$encore = true;
				break 2;
			}
		}
	}

	if ($encore) {
		spip_log("genie_linkcheck_tester_base_dist: on a pas fini, on se relance", 'linkcheck' . _LOG_DEBUG);
		return -($t-300);
	}
	return 0;
}
