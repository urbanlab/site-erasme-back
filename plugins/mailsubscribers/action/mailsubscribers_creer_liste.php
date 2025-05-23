<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}


/**
 * Exporter la base au format CSV
 *
 * @param null|string $statut
 */
function action_mailsubscribers_creer_liste_dist($statut = null) {
	if (is_null($statut)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$statut = $securiser_action();
	}

	if (!autoriser('creer', 'mailsubscribinglist')) {
		include_spip('inc/minipres');
		// on prend la langue la plus probable pour le visiteur du moment qu'elle soit dans $GLOBALS['meta']['langues_multilingue']
		// on affecte $_COOKIE pour que l'appel suivant par minipres ne change rien ensuite
		$_COOKIE['spip_lang'] = utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue']);
		echo minipres();
		exit;
	}

	include_spip('mailsubscribers_fonctions');
	$possibles = mailsubscribers_liste_statut_auteur_possibles();
	if (isset($possibles[$statut])) {
		include_spip('action/editer_objet');
		$set = [
			'titre' => $possibles[$statut],
			'identifiant' => $statut,
			'statut' => 'ouverte'
		];
		if (
			!$id_mailsubscribinglist = sql_getfetsel(
				'id_mailsubscribinglist',
				'spip_mailsubscribinglists',
				'identifiant=' . sql_quote($statut) . ' AND statut=' . sql_quote('poubelle')
			)
		) {
			$id_mailsubscribinglist = objet_inserer('mailsubscribinglist');
		}
		if ($id_mailsubscribinglist) {
			objet_modifier('mailsubscribinglist', $id_mailsubscribinglist, $set);

			// lancer le genie de synchro
			$mailsubscribers_synchro_lists = charger_fonction('mailsubscribers_synchro_lists', 'genie');
			if ($mailsubscribers_synchro_lists and function_exists($mailsubscribers_synchro_lists)) {
				$mailsubscribers_synchro_lists(0);
			}
		}
	}
}
