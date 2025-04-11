<?php

/**
 * Plugin LinkCheck
 * (c) 2013-2017 Benjamin Grapeloux, Guillaume Wauquier
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function linkcheck_pre_propre($texte) {
	if (!function_exists('lire_config')) {
		include_spip('inc/config');
	}
	if (lire_config('linkcheck/traiter_propre',0)) {
		$texte = linkcheck_pre_propre_nettoyer_liens_connus($texte);
	}
	return $texte;
}

function linkcheck_pre_propre_nettoyer_liens_connus($texte) {
	if (strpos($texte, '<a') !== false) {
		$liens = extraire_balises($texte, 'a');
		foreach ($liens as $lien) {
			//var_dump(entites_html($lien));
			$href = extraire_attribut($lien, 'href');
			if ($linkcheck = sql_fetsel('*', 'spip_linkchecks', "url=".sql_quote($href))) {
				if ($linkcheck_remplacer_lien = charger_fonction('linkcheck_remplacer_lien_' . $linkcheck['etat'], 'inc', true)) {
					$lien_corrige = $linkcheck_remplacer_lien($lien, $linkcheck);
					$texte = str_replace($lien, $lien_corrige, $texte);
				}
			}
		}
	}
	return $texte;
}

/**
 * Ajouter aprés l'ajout ou la modification d'un objet, enregistre les
 * nouveaux liens, efface les anciens et programme une vérification de
 * ces liens
 *
 * @param array $flux
 * @return array
 */
function linkcheck_post_edition($flux) {
	if ((!empty($flux['args']['objet']) || !empty($flux['args']['type']))
	  && !empty($flux['args']['id_objet'])
	) {
		include_spip('inc/linkcheck');
		linkcheck_objet_verifier(isset($flux['args']['objet']) ? $flux['args']['objet'] : $flux['args']['type'], $flux['args']['id_objet']);
	}
	return $flux;
}


/**
 * Pipeline qui ajoute des taches automatiques
 *
 * @param array $taches
 * @return $taches
 */
function linkcheck_taches_generales_cron($taches) {
	$taches['linkcheck_tester_base'] = 300; // toutes les 5 minutes on regarde si il y a des sites à tester
	$taches['linkcheck_mail'] = 24 * 3600; // tous les jours
	return $taches;
}

/**
 * Pipeline qui affiche des alertes au webmestre du site, pour l'informer et
 * l'inciter à corriger les liens défectueux du site
 *
 * On n'affiche le message que lorsqu'il y a au moins un lien mort ou malade dans le site
 *
 * @param array $flux
 * @return array
 */
function linkcheck_alertes_auteur($flux) {
	include_spip('inc/config');
	if (lire_config('linkcheck/afficher_alerte')) {
		include_spip('inc/autoriser');
		if (autoriser('voir', 'linkchecks')) {
			include_spip('inc/linkcheck');
			$res = sql_getfetsel('id_linkcheck', 'spip_linkchecks', sql_in('etat', ['mort', 'malade']));
			if ($res > 0) {
				$comptes = linkcheck_chiffre();
				$texte = _T(
					'linkcheck:liens_invalides',
					[
						'mort' => (isset($comptes['nb_lien_mort']) ? $comptes['nb_lien_mort'] : '0'),
						'malade' => (isset($comptes['nb_lien_malade']) ? $comptes['nb_lien_malade'] : '0'),
						'deplace' => (isset($comptes['nb_lien_deplace']) ? $comptes['nb_lien_deplace'] : '0')
					]
				);
				$flux['data'][] = $texte . " <a href='" . generer_url_ecrire('linkchecks') . "'>" . _T('linkcheck:linkcheck') . '</a>';
			}
		}
	}
	return $flux;
}

function linkcheck_affiche_enfants($flux) {
	$e = trouver_objet_exec($flux['args']['exec']);
	if (is_array($e)
		&& !$e['edition']
		&& !empty($flux['args']['id_objet'])
		&& ($id_objet = intval($flux['args']['id_objet']))
	) {
		include_spip('inc/autoriser');
		include_spip('inc/linkcheck');
		$tables_a_traiter = linkcheck_tables_a_traiter();
		if (!empty($tables_a_traiter[$e['table_objet_sql']])
			&& autoriser('modifier', $e['type'], $id_objet)) {
			$id_linkchecks = sql_allfetsel('id_linkcheck', 'spip_linkchecks_liens', "objet=".sql_quote($e['type'])." AND id_objet=".intval($id_objet));
			if (!empty($id_linkchecks)) {
				$id_linkchecks = array_column($id_linkchecks, 'id_linkcheck');
				$flux['data'] .= "<div class='liste-linkchecks-par-objet'>".
					recuperer_fond(
					'prive/objets/liste/linkchecks',
						[
							'id_linkcheck' => $id_linkchecks,
						],
						[
							'ajax' => true,
						]
					)
					."</div>"
				;
			}
		}
	}
	return $flux;
}

/**
 * Optimiser la base de donnees en supprimant les liens orphelins
 * de l'objet vers quelqu'un et de quelqu'un vers l'objet.
 *
 * @param array $flux
 * @return array
 */
function linkcheck_optimiser_base_disparus($flux) {
	include_spip('action/editer_liens');

	$flux['data'] += objet_optimiser_liens(['linkcheck' => '*'], '*');

	// supprimer tous les linkchecks qui ne sont plus liés a aucun contenu
	$ids = sql_allfetsel(
		'L.id_linkcheck',
		'spip_linkchecks AS L LEFT JOIN spip_linkchecks_liens AS LL ON L.id_linkcheck=LL.id_linkcheck',
		'LL.id_linkcheck IS NULL',
		'',
		'',
		'0,1000'
	);
	if (!empty($ids)) {
		spip_log("linkcheck_optimiser_base_disparus: supprimer les linkchecks plus liés à rien : #".implode(', #', $ids), 'linkcheck');
		$ids = array_column($ids, 'id_linkcheck');
		sql_delete('spip_linkchecks', sql_in('id_linkcheck', $ids));
		$flux['data'] += count($ids);
	}

	return $flux;
}
