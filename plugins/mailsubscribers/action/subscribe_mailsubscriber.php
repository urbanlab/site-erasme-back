<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

/**
 * Inscrire un email a une liste (inscription deja en base)
 * (mise a jour du statut en prop ou valide selon l'option double-optin)
 *
 * @param string $email
 * @param array $id_mailsubscribinglists
 * @param null|bool $double_optin
 */
function action_subscribe_mailsubscriber_dist($email = null, $id_mailsubscribinglists = null, $double_optin = null) {
	include_spip('mailsubscribers_fonctions');
	include_spip('inc/mailsubscribers');
	include_spip('inc/config');

	if (is_null($email)) {
		$arg = mailsubscribers_verifier_args_action('subscribe');
		if ($arg) {
			list($email, $id_mailsubscribinglists) = $arg;
		}
	}

	// on prend la langue la plus probable pour le visiteur du moment qu'elle soit dans $GLOBALS['meta']['langues_multilingue']
	// on affecte $_COOKIE pour que l'appel suivant par minipres ne change rien ensuite
	$lang = utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue']);

	$subscriber = charger_fonction('subscriber', 'newsletter');
	if (
		!$email
		or !$infos = $subscriber($email, ['optin_logs' => true])
		or (!empty($infos['optin_logs']) and mailsubscriber_detecte_spambot($infos['optin_logs'], count($infos['subscriptions'] ?? [])))
	) {
		include_spip('inc/mailsubscribers_minipublic');
		$corps = "<div class='msg-alert error'>"
			. _T('info_email_invalide') . '<br />' . entites_html($email)
			. '</div>';
		echo mailsubscribers_minipublic($corps, ['status' => 404, 'lang' => $lang, 'titre' => _T('mailsubscriber:subscribe_titre_email')]);
		exit;
	}

	$nb_listes = 0;
	$titre_liste = '';
	$deja = false;
	$identifiants = null;
	if ($infos['status'] == 'on') {
		$deja = true;
	}
	if ($id_mailsubscribinglists) {
		$titre_liste = [];
		$listes = sql_allfetsel('id_mailsubscribinglist, identifiant, titre_public', 'spip_mailsubscribinglists', sql_in('id_mailsubscribinglist', $id_mailsubscribinglists));
		foreach ($listes as $liste) {
			$identifiant = $liste['identifiant'];
			$status = (isset($infos['subscriptions'][$identifiant]['status']) ? $infos['subscriptions'][$identifiant]['status'] : '');
			if ($status !== 'on') {
				$deja = false;
				$identifiants[] = $identifiant;
				if ($liste['titre_public']) {
					include_spip('inc/texte');
					$titre_liste[] = supprimer_numero(typo($liste['titre_public']));
				}
				else {
					$titre_liste[] = '#' . $liste['id_mailsubscribinglist'];
				}
			}
		}
		$nb_listes = count($titre_liste);
		$titre_liste = implode(', ', $titre_liste);
	}

	$status = 200;
	if ($deja) {
		$corps = "<div class='msg-alert info'>"
			. _T('mailsubscriber:subscribe_deja_texte', ['email' => $email])
			. '</div>';
	}
	else {
		$subscribe = charger_fonction('subscribe', 'newsletter');
		$options = [];
		if (is_null($double_optin)) {
			$double_optin = lire_config('mailsubscribers/double_optin', 0);
		}

		$env = [
			'email' => "<b>$email</b>",
			'nb_listes' => $nb_listes,
			'titre_liste' => $titre_liste,
			'nom_site_spip' => $GLOBALS['meta']['nom_site'],
			'url_site_spip' => $GLOBALS['meta']['adresse_site']
		];
		if ($double_optin) {
			if ($nb_listes > 1) {
				$corps = _T('mailsubscriber:confirmsubscribe_texte_email_listes_1', $env);
			} elseif ($nb_listes == 1) {
				$corps = _T('mailsubscriber:confirmsubscribe_texte_email_liste_1', $env);
			} else {
				$corps = _T('mailsubscriber:confirmsubscribe_texte_email_1', $env);
			}
			$corps .= '<br /><br />'
				. "<div class='msg-alert notice'>"
				. _T('mailsubscriber:confirmsubscribe_texte_email_envoye')
				. '</div>';
		}
		else {
			$options['force'] = true;
			if ($nb_listes > 1) {
				$corps = _T('mailsubscriber:subscribe_texte_email_listes_1', $env);
			} elseif ($nb_listes == 1) {
				$corps = _T('mailsubscriber:subscribe_texte_email_liste_1', $env);
			} else {
				$corps = _T('mailsubscriber:subscribe_texte_email_1', $env);
			}
			$corps = "<div class='msg-alert success'>$corps</div>";
		}

		if ($identifiants) {
			$options['listes'] = $identifiants;
		}
		$subscribe($email, $options);
	}

	// Dans tous les cas on finit sur un minipublic qui dit si ok ou echec
	include_spip('inc/mailsubscribers_minipublic');
	echo mailsubscribers_minipublic($corps, ['status' => $status, 'lang' => $lang, 'titre' => _T('mailsubscriber:subscribe_titre_email')]);
}
