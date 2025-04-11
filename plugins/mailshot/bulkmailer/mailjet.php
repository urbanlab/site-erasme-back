<?php
/**
 * Plugin MailShot
 * (c) 2012 Cedric Morin
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip("inc/config");

/**
 * @param array $to_send
 *   string email
 *   string sujet
 *   string html
 *   string texte
 * @param array $options
 *   bool filtre_images
 *   array smtp
 *     string host
 *     string port
 *     string auth
 *     string username
 *     string password
 *     string secure
 *     string errorsto
 *   string adresse_envoi_nom
 *   string adresse_envoi_email
 * @return null|SPIP\Facteur\FacteurMail
 */
function &bulkmailer_mailjet_dist($to_send,$options=array()){
	static $config = null;
	static $mailer_defaut;
	if (is_null($config)){
		$config = lire_config("mailshot/");
		$mailer_defaut = charger_fonction("defaut","bulkmailer");
	}

	// passer l'api-key de mailshot
	if (empty($options['mailjet_api_key']) and $config['mailjet_api_version'] == 3) {
		$options['mailjet_api_version'] = $config['mailjet_api_version'];
		$options['mailjet_api_key'] = $config['mailjet_api_key'];
		$options['mailjet_secret_key'] = $config['mailjet_secret_key'];
	}
	if (!empty($options['mailjet_api_version']) and $options['mailjet_api_version'] == 3) {
		// on utilise l'API REST
		$options['sender_class'] = "FacteurBulkMailjetv3";
	}

	// sinon (vieille config mailshot) on va retomber sur la config Facteur par defaut si il le permet (un smtp est configure)

	return $mailer_defaut($to_send,$options);
}

/**
 * Configurer mailjet : declarer le sender si besoin
 * appele depuis traiter() de formulaire_configurer_mailshot
 * @param $res
 */
function bulkmailer_mailjet_config_dist(&$res){
	$sender_email = "";

	include_spip('inc/config');
	$config = lire_config('mailshot/');
	if ($config['adresse_envoi']=='oui')
		$sender_email = $config['adresse_envoi_email'];
	else {
		include_spip("inc/facteur");
		$facteur = facteur_factory();
		$sender_email = $facteur->From;
	}

	// si le sender n'est pas dans les emails de mailjet l'ajouter
	if ($sender_email){
		$mailer_factory = charger_fonction('mailjet','bulkmailer');
		$facteur = $mailer_factory([]);

		$facteur->addAuthorizedSender($sender_email, true);
	}

}


/**
 * Prendre en charge le webhook mailjet
 *
 * @param $arg
 */
function bulkmailer_mailjet_webhook_dist($arg){

	if ($_SERVER['REQUEST_METHOD'] == 'HEAD'){
		http_response_code(200);
		exit;
	}

	// les donnes sont postees en JSON RAW
	if (isset($GLOBALS['HTTP_RAW_POST_DATA']) AND $GLOBALS['HTTP_RAW_POST_DATA']){
		$data = $GLOBALS['HTTP_RAW_POST_DATA'];
	}
	// PHP 5.6+ : $GLOBALS['HTTP_RAW_POST_DATA'] obsolete et non peuplee
	else {
		$data = file_get_contents('php://input');
	}
	spip_log("bulkmailer_mailjet_webhook_dist $data","mailshot_feedback");

	if (!$data OR !$events = json_decode($data, true)){
		http_response_code(403);
		exit;
	}

	// si un seul event, on le met dans un tableau pour tout traiter de la meme facon
	if (isset($events['event'])){
		$events = array($events);
	}

	foreach($events as $event){
		// array("open", "click", "bounce", "spam", "blocked", "unsub");
		$quoi = $event['event'];
		if ($quoi=="open") $quoi="read"; // open chez mailjet, read ici
		if ($quoi=="click") $quoi="clic"; // click chez mailjet, clic ici
		if ($quoi=="bounce") $quoi="soft_bounce"; // bounce chez mailjet, soft_bounce ici
		if ($quoi=="blocked") $quoi="reject"; // blocked chez mailjet, reject ici

		$email = $event['email'];
		$tracking_id = $event['customcampaign'];
		if ($tracking_id){
			$tracking_id = explode('/#',$tracking_id);
			if (reset($tracking_id)==protocole_implicite($GLOBALS['meta']['adresse_site'])){
				$tracking_id = end($tracking_id);
				spip_log("tracking $quoi $email $tracking_id",'mailshot_feedback');
				// appeler l'api webhook mailshot avec le raw_feedback en plus pour la trace en base
				$feedback = charger_fonction("feedback","newsletter");
				$feedback("$quoi|".$event['event'],$email,$tracking_id);
			}
		}
	}

	// il faut finir par un status 200 sinon Mailjet considere que c'est un echec
	http_response_code(200);
	echo "OK";
}


/**
 * Initialiser mailjet : declarer un eventcallbackurl pour recuperer les retours sur bounce, reject, open, clic....
 *
 * @param int $id_mailshot
 * @return bool
 */
function bulkmailer_mailjet_init_dist($id_mailshot=0){

	$mailer_factory = charger_fonction('mailjet','bulkmailer');
	$facteur = $mailer_factory([]);

	// si il y a un envoyeur specifique, verifier qu'il est dans les senders
	if ($id_mailshot
		and $mailshot = sql_fetsel('*', 'spip_mailshots', 'id_mailshot=' . intval($id_mailshot))) {
		if (!empty($mailshot['from_email'])) {
			$facteur->addAuthorizedSender($mailshot['from_email'], true);
		}
	}

	$facteur->initWebHooks('newsletter');

	return true;
}
