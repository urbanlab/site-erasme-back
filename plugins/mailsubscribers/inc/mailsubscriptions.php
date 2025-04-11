<?php

/**
 * Un filtre pour afficher le titre d'une liste d'optins éventuellement filtrée par statuts
 * @param $nb
 * @param $statuts
 * @return string
 */
function titre_liste_demandes_optin($nb, $statuts = '') {
	$titre = singulier_ou_pluriel($nb, 'mailsubscriptions_optin:info_1_mailsubscriptions_optin', 'mailsubscriptions_optin:info_nb_mailsubscriptions_optin');
	$titre = $titre ?: _T('mailsubscriptions_optin:info_aucun_mailsubscriptions_optin');
	if (!empty($statuts)) {
		$statuts = (is_array($statuts) ? $statuts : [$statuts]);
		$vals = [];
		foreach ($statuts as $statut) {
			$chaine_statut = 'info_statut_' . $statut . '_short_' . ($nb > 1 ? 'nb' : '1');
			$vals[] = _T('mailsubscriptions_optin:' . $chaine_statut);
		}
		if (count($vals) > 1) {
			$last = array_pop($vals);
			$vals = implode(', ', $vals);
			$vals .= ' ou ' . $last;
		}
		else {
			$vals = reset($vals);
		}
		$titre .= " <i>$vals</i>";
	}
	return $titre;
}

/**
 * @param int $id_mailsubscribinglist
 * @param ?array $subscribers_unread
 * @return int[]
 */
function mailsubscribers_filter_optins_to_send($id_mailsubscribinglist, $subscribers_unread = null) {
	if (is_null($subscribers_unread)) {
		$subscribers_unread = mailsubscribers_mailsubscriptions_unreads($id_mailsubscribinglist);
	}

	// ceux-ci ont déjà reçu théoriquement une demande
	$subscribers_optins_sents = mailsubscribers_mailsubscriptions_optins_sents($id_mailsubscribinglist);

	$subscribers_unread = array_filter($subscribers_unread, function ($entry) use ($subscribers_optins_sents) {
			return !in_array($entry['id_mailsubscriber'], $subscribers_optins_sents);
	});

	return $subscribers_unread;
}





function mailsubscribers_mailsubscriptions_sends(int $id_mailsubscribinglist, ?DateInterval $interval = null): int {
	$shots = mailsubscribers_mailsubscriptions_shots($id_mailsubscribinglist, $interval);
	return count($shots);
}

/** @return int[] */
function mailsubscribers_mailsubscriptions_shots(int $id_mailsubscribinglist, ?DateInterval $interval = null): array {

	$list_name = sql_getfetsel('identifiant', 'spip_mailsubscribinglists', [
		'id_mailsubscribinglist = ' . $id_mailsubscribinglist
	]);

	$interval = (is_null($interval) ? \DateInterval::createFromDateString('1 year') : $interval);
	$date = (new \DateTime())->sub($interval);

	$shots = sql_allfetsel(
		['id_mailshot'],
		['spip_mailshots'],
		[
			'statut = ' . sql_quote('end'),
			'listes = ' . sql_quote($list_name),
			'date > ' . sql_quote($date->format('Y-m-d 00:00:00'))
		],
		'',
		'date DESC',
	);

	return array_column($shots, 'id_mailshot');
}


function mailsubscribers_mailsubscriptions_unreads(int $id_mailsubscribinglist): array {

	return mailsubscribers_mailsubscriptions_search_for_optins(
		$id_mailsubscribinglist,
		\DateInterval::createFromDateString('1 year'),
		['read', 'clic'],
		['sent'],
	);
}

function mailsubscribers_mailsubscriptions_unclics(int $id_mailsubscribinglist): array {

	return mailsubscribers_mailsubscriptions_search_for_optins(
		$id_mailsubscribinglist,
		\DateInterval::createFromDateString('1 year'),
		['clic'],
		['sent', 'read'],
	);
}


function mailsubscribers_mailsubscriptions_search_for_optins(
	int $id_mailsubscribinglist,
	?DateInterval $interval = null,
	array $statuts_without = ['read', 'clic'],
	array $statuts_with = ['sent']
): array {
	$shots = mailsubscribers_mailsubscriptions_shots($id_mailsubscribinglist, $interval);
	$count = count($shots);
	$half = (int) floor($count / 2);

	// quid de statut 'fail' ?
	$excluded_mails = sql_get_select(
		['DISTINCT(email)'],
		['spip_mailshots_destinataires'],
		[
			sql_in('id_mailshot', $shots),
			sql_in('statut', $statuts_without),
		],
	);

	$included_mails = sql_allfetsel(
		['email', 'COUNT(id_mailshot) as nb'],
		['spip_mailshots_destinataires'],
		[
			sql_in('id_mailshot', $shots),
			count($statuts_with) > 1 ? sql_in('statut', $statuts_with) : 'statut=' . sql_quote(reset($statuts_with)),
			"email NOT IN ($excluded_mails)",
		],
		'email',
		'',
		'',
		'nb >= ' . $half
	);

	// Les mails doivent encore exister dans la table des inscrits
	// car ils ont pu se déinscrire ou être supprimés depuis
	$included_mails = array_column($included_mails, 'nb', 'email');
	$mails_found = sql_allfetsel(['id_mailsubscriber', 'email'], 'spip_mailsubscribers', sql_in('email', array_keys($included_mails)), '', 'email');

	// on intègre le compteur nb dedans
	foreach ($mails_found as $k => $entry) {
		$mails_found[$k]['nb'] = $included_mails[$entry['email']];
	}

	return $mails_found;
}


function mailsubscribers_mailsubscriptions_new(int $id_mailsubscribinglist): int {
	return mailsubscribers_mailsubscriptions_count($id_mailsubscribinglist, \DateInterval::createFromDateString('1 year'), ['valide']);
}

function mailsubscribers_mailsubscriptions_count(
	int $id_mailsubscribinglist,
	?DateInterval $interval = null,
	array $statut = ['valide']
): int {
	$interval = (is_null($interval) ? \DateInterval::createFromDateString('1 year') : $interval);

	$date = (new \DateTime())->sub($interval);

	return sql_countsel(
		['spip_mailsubscriptions'],
		[
			sql_in('statut', $statut),
			'id_mailsubscribinglist = ' . $id_mailsubscribinglist,
			'id_segment = 0',
			'maj > ' . sql_quote($date->format('Y-m-d 00:00:00'))
		],
	);
}


/**
 * Liste des id_mailsubscriber faisant deja l'objet d'une demande d'optin depuis moins de 6 mois
 *
 * @param int $id_mailsubscribinglist
 * @param DateInterval|null $interval
 * @return int[]
 */
function mailsubscribers_mailsubscriptions_optins_sents(
	int $id_mailsubscribinglist,
	?DateInterval $interval = null
): array {
	$interval = (is_null($interval) ? \DateInterval::createFromDateString('6 month') : $interval);
	$date = (new \DateTime())->sub($interval);

	$optins = sql_allfetsel(
		['id_mailsubscriber'],
		['spip_mailsubscriptions_optins'],
		[
			'id_mailsubscribinglist = ' . $id_mailsubscribinglist,
			'date > ' . sql_quote($date->format('Y-m-d 00:00:00'))
		],
	);

	return array_column($optins, 'id_mailsubscriber');
}
