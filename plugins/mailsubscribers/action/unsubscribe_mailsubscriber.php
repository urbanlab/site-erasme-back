<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

/**
 * Des-inscrire un email deja en base
 * (mise a jour du statut en refuse)
 *
 * @param string $email
 * @param array $id_mailsubscribinglists
 * @param bool $double_optin
 */
function action_unsubscribe_mailsubscriber_dist($email = null, $id_mailsubscribinglists = null, $double_optin = null, $unconsent = false) {
	include_spip('mailsubscribers_fonctions');
	include_spip('inc/mailsubscribers');

	if (is_null($email)) {
		$arg = mailsubscribers_verifier_args_action('unsubscribe');
		if ($arg) {
			list($email, $id_mailsubscribinglists) = $arg;
		}
		$double_optin = true;
	} else {
		if (is_null($double_optin)) {
			$double_optin = false;
		}
	}

	// on prend la langue la plus probable pour le visiteur du moment qu'elle soit dans $GLOBALS['meta']['langues_multilingue']
	$lang = utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue']);

	$subscriber = charger_fonction('subscriber', 'newsletter');
	if (!$email or !$infos = $subscriber($email)) {
		include_spip('inc/mailsubscribers_minipublic');
		$corps = "<div class='msg-alert error'>"
			. _T('info_email_invalide') . '<br />' . entites_html($email)
			. '</div>';
		echo mailsubscribers_minipublic($corps, ['status' => 404, 'lang' => $lang, 'titre' => _T('mailsubscriber:unsubscribe_titre_email')]);
		exit;
	}

	$nb_listes = 0;
	$titre_liste = '';
	$deja = false;
	$identifiants = null;
	$titre_liste = '';
	if ($infos['status'] !== 'on') {
		$deja = true;
	}
	if ($id_mailsubscribinglists) {
		$titre_liste = [];
		$listes = sql_allfetsel('id_mailsubscribinglist, identifiant, titre_public', 'spip_mailsubscribinglists', sql_in('id_mailsubscribinglist', $id_mailsubscribinglists));
		foreach ($listes as $liste) {
			$identifiant = $liste['identifiant'];
			$status = (isset($infos['subscriptions'][$identifiant]['status']) ? $infos['subscriptions'][$identifiant]['status'] : '');
			if ($status === 'on') {
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
			. _T('mailsubscriber:unsubscribe_deja_texte', ['email' => $email])
			. '</div>';
	}
	else {
		$unsubscribe = charger_fonction('unsubscribe', 'newsletter');
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
			$has_other = false;
			$label_bouton_this = _T('newsletter:bouton_unsubscribe_all');
			if ($nb_listes >= 1) {
				if ($nb_listes > 1) {
					$corps = _T('mailsubscriber:unsubscribe_texte_confirmer_email_listes_1', $env);
					$label_bouton_this = _T('newsletter:bouton_unsubscribe_multiples');
				} elseif ($nb_listes == 1) {
					$corps = _T('mailsubscriber:unsubscribe_texte_confirmer_email_liste_1', $env);
					$label_bouton_this = _T('newsletter:bouton_unsubscribe');
				}
				// si il y a d'autres abonnements valides que ceux la, on met un premier bouton pour le desabonnement a cette/ces newsletters
				foreach ($infos['subscriptions'] as $identifiant => $subscription) {
					if ($subscription['status'] === 'on' and !in_array($identifiant, $identifiants)) {
						$has_other = true;
						break;
					}
				}
				if ($has_other) {
					$corps .= '<br /><br />' . bouton_action(
						$label_bouton_this,
						generer_action_auteur(
							'confirm_unsubscribe_mailsubscriber',
							mailsubscriber_base64url_encode(
								$email .
									':' . implode('-', $id_mailsubscribinglists) .
									':' . time() .
									($unconsent ? ':unconsent' : '')
							)
						)
					);
				}
			}
			else {
				$corps = _T('mailsubscriber:unsubscribe_texte_confirmer_email_1', $env);
			}

			// bouton de desinscription de TOUTES : il n'y aura que celui la present si pas d'autre inscription valide que celle(s) qu'on resilie
			$corps .= '<br /><br />' . bouton_action(
				$has_other ? _T('newsletter:bouton_unsubscribe_all') : $label_bouton_this,
				generer_action_auteur(
					'confirm_unsubscribe_mailsubscriber',
					mailsubscriber_base64url_encode($email . '::' . time() . ($unconsent ? ':unconsent' : ''))
				)
			);
		}
		else {
			$options['force'] = true;
			if ($nb_listes > 1) {
				$corps = _T('mailsubscriber:unsubscribe_texte_email_listes_1', $env);
			} elseif ($nb_listes == 1) {
				$corps = _T('mailsubscriber:unsubscribe_texte_email_liste_1', $env);
			} else {
				$corps = _T('mailsubscriber:unsubscribe_texte_email_1', $env);
			}
			$corps = "<div class='msg-alert success'>$corps</div>";
			if ($identifiants) {
				$options['listes'] = $identifiants;
			}
			$unsubscribe($email, $options);

			if ($unconsent) {
				$reconsent = charger_fonction('reconsent', 'newsletter');
				$options = [
					'listes' => $options['listes'],
					'status' => false,
				];
				$reconsent($email, $options);
			}
		}
	}

	// Dans tous les cas on finit sur un minipublic qui dit si ok ou echec
	include_spip('inc/mailsubscribers_minipublic');
	echo mailsubscribers_minipublic($corps, ['status' => $status, 'lang' => $lang, 'titre' => _T('mailsubscriber:unsubscribe_titre_email')]);
}

function mailsubscriber_base64url_encode($data) {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
