<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// declaration vide pour ce pipeline.
function mailsubscribers_autoriser() {
}


// -----------------
// Objet mailsubscribers


// bouton de menu
function autoriser_mailsubscribers_menu_dist($faire, $type, $id, $qui, $opts) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}

// voir la liste des inscriptions
function autoriser_mailsubscribers_voir_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('voir', 'mailsubscriber', 0, $qui, $opt);
}

// superinstituer : permet de passer outre les restrictions de changement de statut manuel
function autoriser_mailsubscriber_superinstituer_dist($faire, $type, $id, $qui, $opt) {
	return false;
}


// creer
function autoriser_mailsubscriber_creer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo';
}

// iconifier
function autoriser_mailsubscriber_iconifier_dist($faire, $type, $id, $qui, $opt) {
	return false; // pas de logo
}

// voir les fiches completes
function autoriser_mailsubscriber_voir_dist($faire, $type, $id, $qui, $opt) {
	if ($qui['statut'] == '0minirezo' and !$qui['restreint']) {
		return true;
	}

	// un auteur connecte peut toujours voir sa propre fiche mailsubscriber
	if (!empty($GLOBALS['visiteur_session']['email'])) {
		$email = '';
		if (!empty($opt['email'])) {
			$email = $opt['email'];
		}
		elseif ($id) {
			$email = sql_getfetsel('email', 'spip_mailsubscribers', 'id_mailsubscriber=' . intval($id));
		}
		if (!empty($email)) {
			if (
				$GLOBALS['visiteur_session']['email'] === $email
				or mailsubscribers_obfusquer_email($GLOBALS['visiteur_session']['email']) === $email
			) {
				return true;
			}
		}
	}
	return false;
}

// modifier
function autoriser_mailsubscriber_modifier_dist($faire, $type, $id, $qui, $opt) {
	if ($qui['statut'] == '0minirezo' and !$qui['restreint']) {
		return true;
	}

	// un auteur connecte peut toujours voir sa propre fiche mailsubscriber
	if (!empty($GLOBALS['visiteur_session']['email'])) {
		$email = '';
		if (!empty($opt['email'])) {
			$email = $opt['email'];
		}
		elseif ($id) {
			$email = sql_getfetsel('email', 'spip_mailsubscribers', 'id_mailsubscriber=' . intval($id));
		}
		if (!empty($email)) {
			if (
				$GLOBALS['visiteur_session']['email'] === $email
				or mailsubscribers_obfusquer_email($GLOBALS['visiteur_session']['email']) === $email
			) {
				return true;
			}
		}
	}
	return false;
}

// supprimer
function autoriser_mailsubscriber_supprimer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}


// -----------------
// Objet mailsubscribinglists

// voir la liste des listes
function autoriser_mailsubscribinglists_voir_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('voir', 'mailsubscribinglist', 0, $qui, $opt);
}

function autoriser_mailsubscribinglist_voir_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('voir', 'mailsubscriber', 0, $qui, $opt);
}

// creer
function autoriser_mailsubscribinglist_creer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}

function autoriser_mailsubscribinglist_modifier_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}

function autoriser_mailsubscribinglist_supprimer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}

function autoriser_mailsubscribinglist_segmenter_dist($faire, $type, $id, $qui, $opt) {
	if (!function_exists('mailsubscriber_declarer_informations_liees')) {
		include_spip('inc/mailsubscribers');
	}
	if (!test_plugin_actif('saisies')) { return false;
	}
	$declaration = mailsubscriber_declarer_informations_liees();
	if (!$declaration) { return false;
	}

	return autoriser('modifier', $type, $id, $qui, $opt);
}
