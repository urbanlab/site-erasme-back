<?php

function action_demander_optin_list_unread_dist($id_mailsubscribinglist = null): void {
	if (is_null($id_mailsubscribinglist)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_mailsubscribinglist = $securiser_action();
	}

	include_spip('inc/autoriser');
	if (!autoriser('demanderoptin', 'mailsubscribinglist', $id_mailsubscribinglist)) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$row = sql_fetsel('*', 'spip_mailsubscribinglists', 'id_mailsubscribinglist=' . intval($id_mailsubscribinglist));
	if (!$row) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$list_name = $row['identifiant'];
	$log_prefix = sprintf('Liste %s (%s) [unreads] : ', $id_mailsubscribinglist, $list_name);

	include_spip('inc/mailsubscriptions');
	$subscribers_optins_to_send = mailsubscribers_filter_optins_to_send($id_mailsubscribinglist);

	if (!$subscribers_optins_to_send) {
		spip_log(sprintf('%s Aucun subscriber ne nécessite une nouvelle demande d’optin', $log_prefix), 'mailsubscribers');
		return;
	}

	$subscribers_optins_to_send = array_column($subscribers_optins_to_send, 'id_mailsubscriber');

	$inserts = [];
	$date = (new \DateTime())->format('Y-m-d 00:00:00');
	foreach ($subscribers_optins_to_send as $id_mailsubscriber) {
		$inserts[] = [
			'id_mailsubscriber' => $id_mailsubscriber,
			'id_mailsubscribinglist' => $id_mailsubscribinglist,
			'date' => $date,
			'statut' => 'prepa',
		];
	}
	spip_log(sprintf('%s %s subscribers ajoutés pour une demande d’optin', $log_prefix, count($inserts)), 'mailsubscribers');
	sql_insertq_multi('spip_mailsubscriptions_optins', $inserts);
}
