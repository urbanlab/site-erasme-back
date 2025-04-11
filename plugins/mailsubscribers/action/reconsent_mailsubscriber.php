<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

/**
 * Reconsentir un email deja en base
 *
 * @param string $email
 * @param array $id_mailsubscribinglists
 * @param bool $double_optin
 */
function action_reconsent_mailsubscriber_dist($email = null, $id_mailsubscribinglists = null, $double_optin = true) {
	include_spip('mailsubscribers_fonctions');
	include_spip('inc/mailsubscribers');
	include_spip('action/unsubscribe_mailsubscriber');

	if (is_null($email)) {
		$arg = mailsubscribers_verifier_args_action('consent');
		if ($arg) {
			list($email, $id_mailsubscribinglists) = $arg;
		}
	} else {
		$double_optin = false;
	}

	// on prend la langue la plus probable pour le visiteur du moment qu'elle soit dans $GLOBALS['meta']['langues_multilingue']
	$lang = utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue']);

	if (!$id_mailsubscribinglists) {
		include_spip('inc/mailsubscribers_minipublic');
		echo mailsubscribers_minipublic_erreur(null, ['lang' => $lang]);
		exit;
	}

	$subscriber = charger_fonction('subscriber', 'newsletter');
	if (!$email or !$infos = $subscriber($email)) {
		include_spip('inc/mailsubscribers_minipublic');
		$erreur = _T('info_email_invalide') . '<br />' . entites_html($email);
		echo mailsubscribers_minipublic_erreur($erreur, ['lang' => $lang]);
		exit;
	}

	$nb_listes = 0;
	$titre_liste = '';
	$identifiants = null;
	$titre_liste = '';

	$titre_liste = [];
	$listes = sql_allfetsel('id_mailsubscribinglist, identifiant, titre_public', 'spip_mailsubscribinglists', sql_in('id_mailsubscribinglist', $id_mailsubscribinglists));
	foreach ($listes as $liste) {
		$identifiant = $liste['identifiant'];
		$status = (isset($infos['subscriptions'][$identifiant]['status']) ? $infos['subscriptions'][$identifiant]['status'] : '');
		if ($status === 'on') {
			$identifiants[] = $identifiant;
			if ($liste['titre_public']) {
				include_spip('inc/texte');
				$titre_liste[] = supprimer_numero(typo($liste['titre_public']));
			} else {
				$titre_liste[] = '#' . $liste['id_mailsubscribinglist'];
			}
		}
	}

	$nb_listes = count($titre_liste);
	$titre_liste = implode(', ', $titre_liste);

	$status = 200;
	if (!$nb_listes) {
		$corps = "<div class='msg-alert info'>"
			. _T('mailsubscriber:reconsent_subscribe_deja_texte', ['email' => $email])
			. '</div>';
	} else {
		$reconsent = charger_fonction('reconsent', 'newsletter');
		$options = [];

		$env = [
			'email' => "<b>$email</b>",
			'nb_listes' => $nb_listes,
			'titre_liste' => $titre_liste,
			'nom_site_spip' => $GLOBALS['meta']['nom_site'],
			'url_site_spip' => $GLOBALS['meta']['adresse_site']
		];
		if ($double_optin) {
			include_spip('inc/filtres');

			if ($nb_listes > 1) {
				$corps = _T('mailsubscriber:reconsent_subscribe_texte_confirmer_email_listes_1', $env);
				$label_bouton_this = _T('newsletter:bouton_reconsent_subscribe_multiples');
			} elseif ($nb_listes == 1) {
				$corps = _T('mailsubscriber:reconsent_subscribe_texte_confirmer_email_liste_1', $env);
				$label_bouton_this = _T('newsletter:bouton_reconsent_subscribe');
			}

			$corps .= '<br /><br />' . bouton_action(
				$label_bouton_this,
				generer_action_auteur(
					'confirm_reconsent_mailsubscriber',
					mailsubscriber_base64url_encode($email . ':' . implode('-', $id_mailsubscribinglists) . ':' . time())
				)
			);
		} else {
			$options['force'] = true;
			if ($nb_listes > 1) {
				$corps = _T('mailsubscriber:reconsent_subscribe_texte_email_listes_1', $env);
			} elseif ($nb_listes == 1) {
				$corps = _T('mailsubscriber:reconsent_subscribe_texte_email_liste_1', $env);
			} else {
				$corps = _T('mailsubscriber:reconsent_subscribe_texte_email_1', $env);
			}
			$corps = "<div class='msg-alert success'>$corps</div>";
			if ($identifiants) {
				$options['listes'] = $identifiants;
			}
			$reconsent($email, $options);
		}
	}

	// Dans tous les cas on finit sur un minipublic qui dit si ok ou echec
	include_spip('inc/mailsubscribers_minipublic');
	echo mailsubscribers_minipublic($corps, ['status' => $status, 'lang' => $lang, 'titre' => _T('mailsubscriber:reconsent_confirmsubscribe_titre_email')]);
}
