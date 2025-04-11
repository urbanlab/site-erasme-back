<?php
/**
 * Plugin MailShot
 * (c) 2012 Cedric Morin
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Tache periodique d'envoi
 *
 * @param array $taches_generales
 * @return array
 */
function mailshot_taches_generales_cron($taches_generales){

	// on active la tache cron d'envoi uniquement si necessaire (un envoi en cours)
	if (isset($GLOBALS['meta']['mailshot_processing'])
	  and (
			$GLOBALS['meta']['mailshot_processing']==='oui'
			or $GLOBALS['meta']['mailshot_processing']<$_SERVER['REQUEST_TIME']
		)){
		include_spip('inc/mailshot');
		list($periode,$nb) = mailshot_cadence();
		$taches_generales['mailshot_bulksend'] = max(60,$periode-15);
		if (mailshot_fail_ratio_alert_check()) {
			$taches_generales['mailshot_bulksend'] = max(300, $taches_generales['mailshot_bulksend']);
		}
	}

	// gerer les feedback par pooling par imap
	if (isset($GLOBALS["imap_feedback_username"]) && isset($GLOBALS["imap_feedback_password"]) && $GLOBALS["imap_feedback_hostname"]){
		$taches_generales['imap_feedback'] = 3400;
	}

	// depouiller les feedbacks en async, toutes les 5mn
	$taches_generales['feedbacks'] = 300;

	return $taches_generales;
}

/**
 * Etendre le plugin facteur en permettant d'utiliser directement mailjet via l'api et non le smtp
 *
 * @param array $flux
 * @return array
 */
function mailshot_facteur_lister_methodes_mailer($flux) {
	$flux['data']['mailjet'] = array(
		'class' => 'FacteurMailjet',
		'password' => array('mailjet_secret_key'),
	);
	$flux['data']['sendinblue'] = array(
		'class' => 'FacteurSendinblue',
		'password' => array('sendinblue_api_key'),
	);
	$flux['data']['mandrill'] = array(
		'class' => 'FacteurMandrill',
		'password' => array('mandrill_api_key'),
	);

	return $flux;
}

function mailshot_afficher_fiche_objet($flux){
	if ($flux['args']['type']=='mailshot'
	  AND $id_mailshot = intval($flux['args']['id'])){

		$flux['data'] = preg_replace(",(<h1[^>]*>).*(</h1>),Uims","\\1"._T("mailshot:info_mailshot_no",array('id'=>$id_mailshot))."\\2",$flux['data'],1);

		include_spip('inc/mailshot');
		if (mailshot_fail_ratio_alert_check()) {
			$alerte = recuperer_fond("prive/squelettes/contenu/inc-mailshot-alerte-fail-ratio",array());
			$flux['data'] = str_replace("</h1>", "</h1>" . $alerte, $flux['data']);
		}
	}
	return $flux;
}

function mailshot_afficher_complement_objet($flux){
	if ($flux['args']['type']=='mailshot'
	  AND $id_mailshot=intval($flux['args']['id'])){
		#ajouter la liste des envois
		$contexte = array('id_mailshot'=>$id_mailshot);
		if (_request('recherche'))
			$contexte['recherche'] = _request('recherche');
		$flux['data'] .= recuperer_fond("prive/squelettes/contenu/inc-mailshot-destinataires",$contexte,array('ajax'=>true));
	}
	return $flux;
}

/**
 * Quand le statut d'un mailshot change, mettre a jour la date aussi
 *
 * @param $flux
 * @return mixed
 */
function mailshot_pre_edition($flux){
	if ($flux['args']['table']=='spip_mailshots'
	  AND $flux['args']['action']=='instituer'
	  AND $id_mailshot = $flux['args']['id_objet']
	  AND $statut_ancien = $flux['args']['statut_ancien']
	  AND isset($flux['data']['statut'])
		AND !isset($flux['data']['date'])
	  AND $statut = $flux['data']['statut']
	  AND $statut != $statut_ancien){

		$flux['data']['date'] = date('Y-m-d H:i:s');
	}
	return $flux;
}

/**
 * Quand le statut d'un mailshot change, mettre a jour la meta processing
 * + quand une newsletter est depubliee, annuler tous les envois en cours ou planifies correspondants
 *
 * @param $flux
 * @return mixed
 */
function mailshot_post_edition($flux){
	if (isset($flux['args']['table'])
	  AND $flux['args']['table']=='spip_mailshots'
	  AND $flux['args']['action']=='instituer'
	  AND $id_mailshot = $flux['args']['id_objet']
	  AND $statut_ancien = $flux['args']['statut_ancien']
	  AND isset($flux['data']['statut'])
	  AND $statut = $flux['data']['statut']
	  AND $statut != $statut_ancien){

		if (in_array($statut, ['init', 'processing']) || in_array($statut_ancien, ['init', 'processing'])) {
			include_spip("inc/mailshot");
			mailshot_update_meta_processing();
		}
		if ($statut=='cancel') {
			// passer tous les envois to-do en kill
			sql_updateq('spip_mailshots_destinataires', ['statut' => 'kill'], "statut='todo' AND id_mailshot=".intval($id_mailshot));
			// et recompter
			include_spip("inc/mailshot");
			mailshot_compter_envois($id_mailshot);
		}
	}

	if (isset($flux['args']['table'])
	  AND $flux['args']['table']=='spip_newsletters'
	  AND $flux['args']['action']=='instituer'
	  AND $id_newsletter = $flux['args']['id_objet']
	  AND $statut_ancien = $flux['args']['statut_ancien']
	  AND $statut_ancien == 'publie'
	  AND isset($flux['data']['statut'])
	  AND $statut = $flux['data']['statut']
	  AND $statut != $statut_ancien){

		// il y a des mailshot en cours ou en attente pour cette newsletter ?
		$id_mailshots = sql_allfetsel('id_mailshot', 'spip_mailshots', 'id='.intval($id_newsletter). " AND " . sql_in('statut', ['init', 'processing', 'pause']));
		if ($id_mailshots) {
			$id_mailshots = array_column($id_mailshots, 'id_mailshot');
			include_spip('action/editer_objet');
			include_spip('inc/autoriser');
			foreach ($id_mailshots as $id_mailshot) {
				if (autoriser('instituer', 'mailshot', $id_mailshot, '', array('statut' => 'cancel'))) {
					objet_modifier('mailshot', $id_mailshot, array('statut' => 'cancel'));
				}
			}
		}
	}

	return $flux;
}


/**
 * Purger les envois a la pouvelle et le detail des vieux envois
 * @param array $flux
 * @return array
 */
function mailshot_optimiser_base_disparus($flux){
	$n = &$flux['data'];
	$mydate = trim($flux['args']['date'], "'");

	// supprimer les mailshots a la poubelle, plus vieux que $mydate
	$ids = sql_allfetsel("id_mailshot","spip_mailshots","maj<".sql_quote($mydate)." AND statut=".sql_quote('poubelle'));
	$ids = array_map('reset',$ids);

	if (count($ids)){
		spip_log("Purger mailshots poubelle ".implode(",",$ids),"mailshot");
		sql_delete("spip_mailshots_destinataires",sql_in('id_mailshot',$ids));
		sql_delete("spip_mailshots",sql_in('id_mailshot',$ids));
	}
	else {
		// les 2 plus anciens envois finis
		$ids = mailshot_liste_envois_a_archiver(2);
		if ($ids) {
			// on les purge et passe en archive
			include_spip('inc/mailshot');
			while ($ids AND $id_mailshot = array_shift($ids)){
				mailshot_archiver($id_mailshot);
			}
		}
	}

	return $flux;
}

function mailshot_liste_envois_a_archiver($limit=null) {
	$ids = array();

	// sinon archivons les vieux (pas dans le meme appel pour pas etre trop long)
	include_spip("inc/config");
	if (lire_config("mailshot/purger_historique",'non')=='oui'
	  AND $delai = intval(lire_config("mailshot/purger_historique_delai",0))){

		// les envois finis depuis plus de $delai mois, les 2 plus anciens
		// mais jamais le dernier envoi (on le garde meme si le site n'est plus tres actif en envois)
		$id_last = sql_getfetsel('id_mailshot', 'spip_mailshots', sql_in('statut', array('end', 'cancel')), '', 'date DESC', '0,1');
		$vieux = date('Y-m-d H:i:s', strtotime("-$delai month"));
		$ids = sql_allfetsel(
			"id_mailshot",
			"spip_mailshots",
			"date<" . sql_quote($vieux)
			. " AND id_mailshot<>" . intval($id_last)
			. " AND (date_start<date OR date_start<" . sql_quote($vieux) . ")"
			. " AND " . sql_in('statut', array('end', 'cancel')), "", "id_mailshot", ($limit ? "0,$limit" : ''));
		$ids = array_column($ids, 'id_mailshot');
	}

	return $ids;
}
