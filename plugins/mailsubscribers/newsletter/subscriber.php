<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

include_spip('inc/mailsubscribers');
include_spip('mailsubscribers_fonctions');

/**
 * Decrit les informations d'un inscrit
 * Pour retirer une liste il faut desinscrire
 *
 * @param string $email
 *   champ obligatoire
 * @param array $options
 *   array :
 *     array listes : liste(s) pour l'unsubscribe
 *     bool optin_logs : récupérer les logs d'optin si possible
 * @return bool|array
 *   false si n'existe pas
 *   array :
 *     string email
 *     string nom
 *     deprecated array listes
 *     string lang
 *     string status : on|pending|off
 *     array subscriptions
 *     string url_unsubscribe : url de desabonnement
 *     string optin_logs : si demandés et si dispos
 */
function newsletter_subscriber_dist($email, $options = []) {

	// chercher si un tel email est deja en base
	// on utilise sql_allfetsel car normalement on a 0 ou 1 resultat, sauf en cas de doublon email+obfusque -> 2 resultats
	$infos_all = sql_allfetsel(
		"email,nom,'' as listes,lang,'' as status,jeton,id_mailsubscriber",
		'spip_mailsubscribers',
		'statut!=\'poubelle\' AND (email=' . sql_quote($email) . ' OR email=' . sql_quote(mailsubscribers_obfusquer_email($email)) . ')'
	);
	$infos = ($infos_all ? reset($infos_all) : false);
	if (count($infos_all) > 1) {
		$mailsubscribers_fusionner_doublons = charger_fonction('mailsubscribers_fusionner_doublons', 'action');
		$infos = $mailsubscribers_fusionner_doublons($email, $infos_all);
	}
	if ($infos) {
		$id_mailsubscriber = $infos['id_mailsubscriber'];
		$infos = mailsubscribers_informe_subscriber($infos);
		$infos['url_admin'] = generer_url_entite($id_mailsubscriber, 'mailsubscriber', '', '', false);
		if ($infos['email'] !== $email) {
			$infos['url_admin'] = parametre_url($infos['url_admin'], 'email', $email);
		}

		// si on est dans le contexte d'une liste unique connue, modifier l'url_unsubscribe
		if (
			isset($options['listes'])
			and is_array($options['listes'])
			and count($options['listes']) == 1
			and $id_et_segment = reset($options['listes'])
			and $id_et_segment = explode('+', $id_et_segment)
			and $id = reset($id_et_segment)
			and $id = mailsubscribers_normaliser_nom_liste($id)
			and isset($infos['subscriptions'][$id]['url_unsubscribe'])
		) {
			$infos['url_unsubscribe'] = $infos['subscriptions'][$id]['url_unsubscribe'];
		}

		if (!empty($options['optin_logs'])) {
			$infos['optin_logs'] = '';
			if ($optin = sql_getfetsel('optin', 'spip_mailsubscribers', 'id_mailsubscriber=' . intval($id_mailsubscriber))) {
				$infos['optin_logs'] = $optin;
			}
		}
		return $infos;
	}

	return false;
}
