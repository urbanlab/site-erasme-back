<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

include_spip('action/editer_objet');
include_spip('inc/mailsubscribers');
include_spip('inc/config');
include_spip('inc/autoriser');

/**
 * Reconsentir un subscriber par son email
 *
 * si une ou plusieurs listes precisees, le subscriber reconsent ces seules listes
 * si aucune liste precisee, le subscriber ne reconsent rien (on ne fait rien)
 * par défaut l'état du reconsent est true (oui j'accepte), mais si on passe un status false, c'est un unconsent
 *
 * @param $email
 *   champ obligatoire
 * @param array $options
 *   array $listes
 *   ?bool $status : status du consentement (true par defaut)
 * @return bool
 *   true si inscrit, false sinon
 */
function newsletter_reconsent_dist($email, $options = []) {
	static $dejala = false;
	if ($dejala) {return false;
	}

	$status = (isset($options['status']) ? $options['status'] : true);
	$log_action = ($status ? 'accepte' : 'refuse');

	// chercher si un tel email est deja en base
	$row = sql_fetsel('*', 'spip_mailsubscribers', 'email=' . sql_quote($email));
	if (!$row) {
		$row = sql_fetsel('*', 'spip_mailsubscribers', 'email=' . sql_quote(mailsubscribers_obfusquer_email($email)));
	}
	if ($row) {
		$id_mailsubscriber = intval($row['id_mailsubscriber']);
		$ids = null;
		$where = [
			'statut = ' . sql_quote('prop'),
			'id_mailsubscriber = ' . $id_mailsubscriber,
		];

		if (
			isset($options['listes'])
			and is_array($options['listes'])
		) {
			$listes = array_map('mailsubscribers_normaliser_nom_liste', $options['listes']);
			$ids = sql_allfetsel('id_mailsubscribinglist', 'spip_mailsubscribinglists', sql_in('identifiant', $listes));
			$ids = array_column($ids, 'id_mailsubscribinglist');
			$where[] = sql_in('id_mailsubscribinglist', $ids);
			// chercher tous les demandes d’optin envoyées pour ces listes à cette personne
			$optins = sql_allfetsel('*', 'spip_mailsubscriptions_optins', $where, '', 'date DESC');
		} else {
			// FIXME: cas d’aucune liste indiquée partiellement traité
			// probablement depuis "Maintenir mon inscription à toutes les newsletter" :
			// on valide toutes les demandes d’optins en cours (mais il y a peut être plus de listes où le subscriber est inscrit)

			// chercher tous les demandes envoyées d’optin pour cette personne
			$optins = sql_allfetsel('*', 'spip_mailsubscriptions_optins', $where, '', 'date DESC');
			$ids = array_column($optins, 'id_mailsubscribinglist');
			$listes = sql_allfetsel('identifiant', 'spip_mailsubscribinglists', sql_in('id_mailsubscribinglist', $ids));
			$listes = array_column($listes, 'identifiant');
		}

		if ($ids) {
			spip_log(
				sprintf("reconsentirmailsubscriptions %s (#%s) $log_action les listes %s (%s)", $email, $id_mailsubscriber, implode(', ', $listes), implode(', ', $ids)),
				'mailsubscribers.' . _LOG_INFO_IMPORTANTE
			);
			sql_updateq(
				'spip_mailsubscriptions_optins',
				[
					'statut' => $status ? 'valide' : 'refuse',
					'date' => (new \DateTime())->format('Y-m-d 00:00:00'),
				],
				[
					sql_in('id_mailsubscriptions_optin', array_column($optins, 'id_mailsubscriptions_optin'))
				]
			);

			// l’indiquer direcetement sur le subscriber
			$trace_optin = '';
			foreach ($listes as $liste) {
				$trace_optin .= '[' . $liste . ':reconsent ' . ($status ? ' OK' : ' NIET') . ']';
			}
			$old_trace = sql_getfetsel('optin', 'spip_mailsubscribers', 'id_mailsubscriber=' . $id_mailsubscriber);
			$new_trace = mailsubscribers_trace_optin($trace_optin, $old_trace);
			sql_updateq('spip_mailsubscribers', ['optin' => $new_trace], 'id_mailsubscriber=' . $id_mailsubscriber);
		} else {
			spip_log(
				sprintf('reconsentirmailsubscriptions %s (#%s) sans demande d’optin trouvé ???', $email, $id_mailsubscriber),
				'mailsubscribers.' . _LOG_INFO_IMPORTANTE
			);
		}
	}

	return true;
}
