<?php
/**
 * Plugin MailShot
 * (c) 2012 Cedric Morin
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip("inc/config");

/**
 * Permettre la prise en compte d'un evenement concernant un mail envoye
 * (tracking de l'ouverture, clic, bounce...)
 * pour mettre a jour le status de l'envoi correspondant
 *
 * @param string $quoi
 *   read : l'email a ete ouvert
 *   clic : un lien a ete clique
 *   soft_bounce : refus temporaire pour cause de boite mail pleine ou autre
 *   hard_bounce : adresse foireuse, refus definitif
 *   reject : email rejete
 *   spam : email taggue en spam
 *   unsub : demande de desinscription
 * @param string $email
 * @param string $tracking_id
 * @param bool $is_cron
 */
function newsletter_feedback_dist($quoi,$email,$tracking_id, $is_cron = false){
	static $file;

	// si on est pas dans un cron, il faut eviter de consommer des ressources car on peut avoir beaucoup d'appels concurrent par webhook
	// on enregistre les infos dans un fichier a depouiller en cron
	if (!$is_cron) {

		if (!$file){
			$file = sous_repertoire(_DIR_TMP, 'newsletter_feedbacks') . substr(md5(@getmypid() . '-' . $_SERVER['REQUEST_TIME']), 0, 8);
		}
		// tous les feedbacks d'un meme hit dans le meme file, une ligne de JSON par feedback
		$row = json_encode(array($quoi, $email, $tracking_id)) . "\n";
		// pas de concurrence puisque fichier propre au hit, on file_put_contents au plus vite, sans lock ni precaution
		file_put_contents($file, $row, FILE_APPEND);

	}
	else {

		newsletter_feedback_un($quoi,$email,$tracking_id);

	}
}

/**
 * Depouiller un lot de feedbacks (issu d'un fichier)
 * @param array $feedbacks
 */
function newsletter_feedback_lot($feedbacks) {
	$recompte_mailshots = array();

	$nb = count($feedbacks);
	spip_log('newsletter_feedback_lot : '.$nb.' feedbacks', 'newsletter_feedback');

	foreach($feedbacks as $feedback) {
		if (count($feedback)>=3) {
			list($quoi, $email, $tracking_id) = $feedback;
			if ($id_mailshot = newsletter_feedback_un($quoi, $email, $tracking_id, false)) {
				$recompte_mailshots[$id_mailshot] = true;
			}
		}
		else {
			spip_log('feedback lot mal formate : '.var_export($feedback, true), 'newsletter_feedback');
		}
	}

	// et on demande un recomptage async des mailshots vus
	if (count($recompte_mailshots)) {
		$recompte_mailshots = array_keys($recompte_mailshots);
		foreach ($recompte_mailshots as $id_mailshot) {
			job_queue_add("mailshot_compter_envois","mailshot_compter_envois",array($id_mailshot),"inc/mailshot",true);
		}
	}

}


/**
 * Prendre en compte un feedback, en lancant un recomptage si besoin
 * @param string $quoi
 * @param string $email
 * @param string $tracking_id
 * @param bool $recompter
 * @return int|bool
 *   id_mailshot du feedback si il y a eu modif, false sinon
 */
function newsletter_feedback_un($quoi,$email,$tracking_id, $recompter = true) {

	// quoi peut contenir en sus le raw_feedback du presta accolé avec un |
	// ex : reject|blocked => le presta a renvoyé un 'blocked' qui a été normalisé en 'reject'
	$quoi_extended = explode('|', $quoi, 2);
	$quoi = array_shift($quoi_extended);
	$raw_feedback = (!empty($quoi_extended) ? reset($quoi_extended) : '');

	if (!in_array($quoi,array('read','clic','soft_bounce','hard_bounce','reject','spam', 'unsub'))){
		spip_log("$quoi inconnu ","newsletter_feedback"._LOG_INFO_IMPORTANTE);
		return false;
	}

	if (!preg_match(',^mailshot(\d+)(-\d+)?$,',$tracking_id,$m)
		OR !intval($id_mailshot=$m[1])){
		spip_log("tracking_id $tracking_id inconnu","newsletter_feedback"._LOG_INFO_IMPORTANTE);
		return false;
	}

	if (!$row = sql_fetsel("*","spip_mailshots_destinataires","id_mailshot=".intval($id_mailshot)." AND email=".sql_quote($email))){
		spip_log("email $email introuvable dans lot mailshot #$id_mailshot","newsletter_feedback"._LOG_INFO_IMPORTANTE);
		return false;
	}

	$set = array();
	$desabonner = false;

    // $row['statut'] in todo, sent, fail, [read, [clic]],[spam]
	// ok on a tout ce qu'il faut, avisons
	switch($quoi){
		case 'read':
			if (in_array($row['statut'],array('todo','sent','fail','spam')))
				$set['statut'] = 'read';
			break;
		case 'clic':
			if (in_array($row['statut'],array('todo','sent','fail','spam','read')))
				$set['statut'] = 'clic';
			break;
		case 'spam':
			if (in_array($row['statut'],array('todo','sent','fail','read'))) {
				$set['statut'] = 'spam';
				$desabonner = _MAILSHOT_DESABONNER_FAILED;
			}
			break;
		case 'unsub':
			// demande explicite de desinscription via l'entete mail ajouter par le fournisseur d'envois de mails
			// on passe le statut de l'envoi a spam : ce n'est pas tout a fait exact mais ca permet de comprendre la desinscription
			// le desabonner ne suit pas la constante _MAILSHOT_DESABONNER_FAILED car c'est une demande ferme et definitive
			if (in_array($row['statut'],array('todo','sent','fail'))) {
				$set['statut'] = 'spam';
			}
			// meme si le statut ne change pas car on veut garder la trace que le destinataire a lu ou clique,
			// on desabonne car c'est SON PROJET
			$desabonner = true;
			break;
		case 'reject':
		case 'hard_bounce':
			if (in_array($row['statut'],array('todo','sent','fail'))){
				$set['statut'] = 'fail';
				$desabonner = (_MAILSHOT_DESABONNER_FAILED ? 'check' : false);
			}
			break;
		case 'soft_bounce':
			if (in_array($row['statut'],array('todo','sent','fail'))) {
				$set['statut'] = 'fail';
				$desabonner = (_MAILSHOT_DESABONNER_FAILED ? 'checksoft' : false);
			}
			break;
	}

	if (count($set) or $desabonner){

		// si modif de statut ou desabonnement, on enregistre le raw_feedback associe
		$set['raw_feedback'] = $quoi . ($raw_feedback ? "|$raw_feedback" : '');
		spip_log("lot #$id_mailshot | ".$set['raw_feedback']." $email : passe en statut=".($set['statut'] ?? 'inchange') . ($desabonner===true ? " ( et unsubscribe)" : ''),"newsletter_feedback"._LOG_INFO_IMPORTANTE);
		sql_updateq("spip_mailshots_destinataires",$set,"id_mailshot=".intval($id_mailshot)." AND email=".sql_quote($email));

		if ($desabonner){
			if (in_array($desabonner, ['check', 'checksoft'], true)) {
				$seuil = (defined('_MAILSHOT_MAX_FAIL') ? _MAILSHOT_MAX_FAIL : 3);
				if ($desabonner === 'checksoft') {
					$seuil = $seuil * 2;
				}
				include_spip('inc/mailshot');
				mailshot_verifier_email_fail($email, 0, 5, "Feedback $quoi sur mailshot#$id_mailshot", $seuil);
			}
			else {
				$unsubscribe = charger_fonction("unsubscribe","newsletter");
				$unsubscribe($email,array('notify'=>false, 'comment' => "Feedback $quoi sur mailshot#$id_mailshot"));
			}
		}

		if ($recompter) {
			// et on demande un recomptage async
			job_queue_add("mailshot_compter_envois","mailshot_compter_envois",array($id_mailshot),"inc/mailshot",true);
		}
		return $id_mailshot;
	}
	else {
		spip_log("lot #$id_mailshot | $quoi $email ras","newsletter_feedback");
	}

	return false;
}
