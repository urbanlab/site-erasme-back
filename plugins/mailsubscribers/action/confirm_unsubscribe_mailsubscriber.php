<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}


/**
 * Confirmer la desinscription d'un email
 *
 * appelle par la page de desinscription pour eviter les deinscriptions intempestives
 * par les fournisseurs de mail qui cliquent automatiquement sur les liens
 * -> verifie que le clic n'a pas ete trop rapide (moins de 1s)
 * -> valide la desinscription si ok, affiche un message sinon incitant l'humain a recliquer
 *
 * @param string $email
 * @param array $id_mailsubscribinglists
 */
function action_confirm_unsubscribe_mailsubscriber_dist($email = null, $id_mailsubscribinglists = null) {
	include_spip('mailsubscribers_fonctions');
	// on prend la langue la plus probable pour le visiteur du moment qu'elle soit dans $GLOBALS['meta']['langues_multilingue']
	// on affecte $_COOKIE pour que l'appel suivant par minipres ne change rien ensuite
	$_COOKIE['spip_lang'] = utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue']);

	$timestamp = null;
	$unconsent = false;
	if (is_null($email)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
		$arg = mailsubscriber_base64url_decode($arg);
		$arg = explode(':', $arg);
		$timestamp = array_pop($arg);
		if ($timestamp === 'unconsent') {
			$unconsent = true;
			$timestamp = array_pop($arg);
		}
		$id_mailsubscribinglists = array_pop($arg);
		$id_mailsubscribinglists = explode('-', $id_mailsubscribinglists);
		$email = implode(':', $arg);
	}

	// si l'URL de confirmation contient un timestamp trop recent c'est que le clic a ete vraiment super rapide
	// on suspecte donc que c'est un bot
	// si c'est un humain il n'a qua recharger la page pour que ca passe
	if ($timestamp and $timestamp >= $_SERVER['REQUEST_TIME'] - 1) {
		include_spip('inc/mailsubscribers_minipublic');
		$erreur = _T('mailsubscriber:texte_vous_avez_clique_vraiment_tres_vite');
		echo mailsubscribers_minipublic_erreur($erreur, ['titre' => _T('mailsubscriber:unsubscribe_titre_email')]);
		exit;
	}

	// il suffit de rejouer unsubscribe en forcant le simple-optin
	$unsubscribe_mailsubscriber = charger_fonction('unsubscribe_mailsubscriber', 'action');
	$unsubscribe_mailsubscriber($email, $id_mailsubscribinglists, false, $unconsent);
}

function mailsubscriber_base64url_decode($data) {
	return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}
