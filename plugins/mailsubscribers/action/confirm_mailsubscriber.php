<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

/**
 * Confirmer l'inscription d'un email a une liste (inscription deja en base)
 * (appelle lors du double-optin : delegue a subscribe le changement de statut en valide)
 *
 * @param string $email
 * @param array $id_mailsubscribinglists
 */
function action_confirm_mailsubscriber_dist($email = null, $id_mailsubscribinglists = null) {

	include_spip('mailsubscribers_fonctions');
	include_spip('inc/mailsubscribers');

	// on prend la langue la plus probable pour le visiteur du moment qu'elle soit dans $GLOBALS['meta']['langues_multilingue']
	// on affecte $_COOKIE pour que l'appel suivant par minipres ne change rien ensuite
	$_COOKIE['spip_lang'] = utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue']);

	if (is_null($email)) {
		$arg = mailsubscribers_verifier_args_action('confirm');
		if ($arg) {
			list($email, $id_mailsubscribinglists) = $arg;
		}
	}

	if (!$email) {
		include_spip('inc/mailsubscribers_minipublic');
		$erreur = _T('info_email_invalide') . '<br />' . entites_html($email);
		echo mailsubscribers_minipublic_erreur($erreur, ['titre' => _T('mailsubscriber:subscribe_titre_email')]);
		exit;
	}

	$subscribe_mailsubscriber = charger_fonction('subscribe_mailsubscriber', 'action');
	$subscribe_mailsubscriber($email, $id_mailsubscribinglists, false);
}
