<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

include_spip('inc/actions');
include_spip('inc/mailsubscribers');
include_spip('inc/editer');
include_spip('inc/autoriser');

/**
 * Declarer les champs postes et y integrer les valeurs par defaut
 */
function formulaires_editer_email_subscription_charger_dist($email) {

	if (!$email) {
		return false;
	}
	$listes_dispos = mailsubscribers_listes();
	if (!$listes_dispos) {
		return false;
	}

	$valeurs = [
		'listes' => [],
		'_listes_dispo' => $listes_dispos,
		'_email' => $email,
		'_id_mailsubscriber' => '',
		'editable' => ' ',
	];

	$subscriber = charger_fonction('subscriber', 'newsletter');
	$infos = $subscriber($email);

	if ($infos and isset($infos['subscriptions'])) {
		$valeurs['_id_mailsubscriber'] = parametre_url($infos['url_admin'], 'id_mailsubscriber');
		foreach ($infos['subscriptions'] as $sub) {
			if ($sub['status'] !== 'off') {
				$valeurs['listes'][] = $sub['id'];
			}
		}
	}

	$id = (empty($valeurs['_id_mailsubscriber']) ? 0 : $valeurs['_id_mailsubscriber']);
	if (!autoriser('voir', 'mailsubscriber', $id, null, ['email' => $email])) {
		return false;
	}

	if (!autoriser('modifier', 'mailsubscriber', $id, null, ['email' => $email])) {
		$valeurs['editable'] = '';
	}

	return $valeurs;
}

/**
 * Verifier les champs postes et signaler d'eventuelles erreurs
 */
function formulaires_editer_email_subscription_verifier_dist($email) {
	$erreurs = [];

	return $erreurs;
}

/**
 * Traiter les champs postes
 */
function formulaires_editer_email_subscription_traiter_dist($email) {
	$listes = _request('listes');
	if (!$listes) {
		$listes = [];
	}

	$subscriber = charger_fonction('subscriber', 'newsletter');
	$infos = $subscriber($email);
	$remove = false;
	$add = $listes;
	if ($infos && $infos['subscriptions']) {
		$add = array_diff($add, array_keys($infos['subscriptions']));
		foreach ($infos['subscriptions'] as $sub) {
			if (in_array($sub['id'], $listes) and $sub['status'] == 'off') {
				$add[] = $sub['id'];
			} elseif (!in_array($sub['id'], $listes) and $sub['status'] !== 'off') {
				$remove[] = $sub['id'];
			}
		}
	}
	// les ajouts sont directement en valide, sans notification
	if ($add) {
		$subscribe = charger_fonction('subscribe', 'newsletter');
		$subscribe($email, ['listes' => $add, 'force' => true, 'notify' => false]);
	}
	// les ajouts sont directement en valide, sans notification
	if ($remove) {
		$unsubscribe = charger_fonction('unsubscribe', 'newsletter');
		$unsubscribe($email, ['listes' => $remove, 'notify' => false]);
	}

	$res = ['editable' => true, 'message_ok' => ''];

	return $res;
}
