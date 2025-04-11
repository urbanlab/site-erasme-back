<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

// Notifier une demande de reconsentement d'une ou plusieurs inscriptions a des listes (d'un meme subscriber)
function notifications_reconsentirmailsubscription_dist($quoi, $id_mailsubscriber, $options) {

	$envois = [];
	include_spip('inc/texte');

	if (
		isset($options['subscriptions'])
		and count($options['subscriptions'])
	) {
		foreach ($options['subscriptions'] as $subscription) {
			if (!isset($subscription['id_mailsubscribinglist'])) {
				spip_log("reconsentirmailsubscriptions #$id_mailsubscriber : id_mailsubscribinglist inconnu " . json_encode($subscription), 'notifications' . _LOG_INFO_IMPORTANTE);
				continue; // rien d'autre a faire pour cette subscription
			}
			$id_mailsubscribinglist = $subscription['id_mailsubscribinglist'];

			if ($subscription['statut_ancien'] !== 'valide') {
				spip_log("reconsentirmailsubscriptions #$id_mailsubscriber a liste #$id_mailsubscribinglist : statut invalide", 'notifications' . _LOG_INFO_IMPORTANTE);
				continue; // rien d'autre a faire pour cette subscription
			}

			// trouver le modele d'envoi
			$modele = 'notifications/mailsubscriber_reconsent';

			if ($modele) {
				if (!isset($envois[$modele])) {
					$envois[$modele] = [
						'id_mailsubscribinglists' => [],
						'contexte' => [],
					];
				}

				$envois[$modele]['id_mailsubscribinglists'][] = $id_mailsubscribinglist;
				$envois[$modele]['contexte'][$id_mailsubscribinglist] = $subscription;
			}
		}
	}

	spip_log("reconsentirmailsubscriptions #$id_mailsubscriber : " . count($envois) . ' mails differents a envoyer', 'notifications');

	if ($envois) {
		$contexte = $options;
		unset($contexte['subscriptions']);
		$contexte['id_mailsubscriber'] = $id_mailsubscriber;

		$infos = sql_fetsel('email, lang', 'spip_mailsubscribers', 'id_mailsubscriber=' . intval($id_mailsubscriber));
		$destinataires = pipeline(
			'notifications_destinataires',
			[
				'args' => ['quoi' => $quoi, 'id' => $id_mailsubscriber, 'options' => $options],
				'data' => [$infos['email']]
			]
		);

		// precaution : enlever les adresses en "@example.org"
		if (!is_array($destinataires)) {
			$destinataires = [$destinataires];
		}
		foreach ($destinataires as $k => $email) {
			if (preg_match(',@example.org$,i', $email)) {
				unset($destinataires[$k]);
			}
		}

		if (count($destinataires)) {
			$envoyer_mail = charger_fonction('envoyer_mail', 'inc'); // pour nettoyer_titre_email

			// envoyer dans la langue du subscriber si connue (par defaut c'est celle du site en général)
			if (!empty($infos['lang'])) {
				$contexte['lang'] = $infos['lang'];
			}

			foreach ($envois as $modele => $envoi) {
				if (
					count($envoi['id_mailsubscribinglists']) > 1
					and $modele_multiples = "$modele-multiples"
					and trouver_fond($modele_multiples)
				) {
					spip_log("reconsentirmailsubscriptions #$id_mailsubscriber : $modele_multiples : envoi en un seul mail pour listes #" . implode(', #', $envoi['id_mailsubscribinglists']), 'notifications' . _LOG_INFO_IMPORTANTE);

					$env = [];
					while (count($envoi['contexte'])) {
						$env = array_merge($env, array_shift($envoi['contexte']));
					}
					$env = array_merge($env, $contexte);
					$env['id_mailsubscribinglists'] = $envoi['id_mailsubscribinglists'];
					unset($env['statut']);

					$texte = recuperer_fond($modele_multiples, $env);
					notifications_envoyer_mails($destinataires, $texte);
				} else {
					foreach ($envoi['id_mailsubscribinglists'] as $id_mailsubscribinglist) {
						spip_log("reconsentirmailsubscriptions #$id_mailsubscriber : $modele : envoi mail pour liste #$id_mailsubscribinglist", 'notifications' . _LOG_INFO_IMPORTANTE);

						$env = array_merge($envoi['contexte'][$id_mailsubscribinglist], $contexte);
						unset($env['statut']);
						$env['id_mailsubscribinglist'] = $id_mailsubscribinglist;

						$texte = recuperer_fond($modele, $env);
						notifications_envoyer_mails($destinataires, $texte);
					}
				}
			}
		} else {
			spip_log("reconsentirmailsubscriptions #$id_mailsubscriber : aucun destinataire - rien a faire", 'notifications' . _LOG_INFO_IMPORTANTE);
		}
	}
}
