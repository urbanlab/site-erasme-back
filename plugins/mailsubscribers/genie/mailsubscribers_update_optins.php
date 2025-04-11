<?php

/**
 * CRON d’analyse des optins demandés à certains subscribers
 *
 * @param $t
 * @return int
 */
function genie_mailsubscribers_update_optins_dist($t) {

	mailsubscribers_update_optins_prepa_to_prop();
	mailsubscribers_update_optins_prop_to_outdated();

	return 1;
}

function mailsubscribers_update_optins_prepa_to_prop(?int $max = 10): void {
	$optins = sql_allfetsel(
		[
			'id_mailsubscriptions_optin',
			'id_mailsubscriber',
			'id_mailsubscribinglist',
		],
		'spip_mailsubscriptions_optins',
		[
			'statut = ' . sql_quote('prepa'),
		],
		'',
		'date ASC',
		'0, ' . $max
	);

	if (!$optins) {
		return;
	}

	spip_log(sprintf('On demande à %s subscribers une vérification d’optin', count($optins)), 'mailsubscribers');
	$notifications = charger_fonction('notifications', 'inc');

	foreach ($optins as $optin) {
		$statut = sql_getfetsel(
			'statut',
			'spip_mailsubscriptions',
			[
				'id_mailsubscriber = ' . $optin['id_mailsubscriber'],
				'id_mailsubscribinglist = ' . $optin['id_mailsubscribinglist'],
				'id_segment = 0',
			]
		);

		$notifications_options = [
			'subscriptions' => [
				[
					'id_mailsubscribinglist' => $optin['id_mailsubscribinglist'],
					'statut' => 'valide',
					'statut_ancien' => $statut,
				],
			],
		];
		$notifications('reconsentirmailsubscription', $optin['id_mailsubscriber'], $notifications_options);
		sql_updateq(
			'spip_mailsubscriptions_optins',
			[
				'statut' => 'prop',
				'date' => (new \DateTime())->format('Y-m-d 00:00:00'),
			],
			['id_mailsubscriptions_optin = ' . $optin['id_mailsubscriptions_optin']],
		);
	}
}

/**  */
function mailsubscribers_update_optins_prop_to_outdated(?int $max = 10, ?\DateInterval $interval = null): void {
	$interval = (is_null($interval) ? \DateInterval::createFromDateString('7 days') : $interval);
	$date = (new \DateTime())->sub($interval);

	$optins = sql_allfetsel(
		[
			'id_mailsubscriptions_optin',
			'id_mailsubscriber',
			'id_mailsubscribinglist',
		],
		'spip_mailsubscriptions_optins',
		[
			'statut = ' . sql_quote('prop'),
			'date < ' . sql_quote($date->format('Y-m-d 00:00:00'))
		],
		'',
		'date ASC',
		'0, ' . $max
	);

	if (!$optins) {
		return;
	}

	spip_log(sprintf('%s subscribers ont leur demande d’optin expirée', count($optins)), 'mailsubscribers');
	foreach ($optins as $optin) {
		$email = sql_getfetsel('email', 'spip_mailsubscribers', ['id_mailsubscriber = ' . (int) $optin['id_mailsubscriber']]);
		$liste = sql_getfetsel('identifiant', 'spip_mailsubscringlists', ['id_mailsubscribinglist = ' . (int) $optin['id_mailsubscribinglist']]);

		spip_log(sprintf('Subscriber %s (#%s) désinscrit de %s (#%s) car optin expiré', $email, $optin['id_mailsubscriber'], $liste, $optin['id_mailsubscribinglist']), 'mailsubscribers.' . _LOG_INFO_IMPORTANTE);

		$unsubscribe = charger_fonction('unsubscribe', 'newsletter');
		$unsubscribe($email, ['notify' => false, 'listes' => [$liste]]);

		sql_updateq(
			'spip_mailsubscriptions_optins',
			[
				'statut' => 'outdated',
				'date' => (new \DateTime())->format('Y-m-d 00:00:00'),
			],
			[
				'id_mailsubscriptions_optin = ' . $optin['id_mailsubscriptions_optin']
			],
		);
	}
}
