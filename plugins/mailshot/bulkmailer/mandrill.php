<?php
/**
 * Plugin MailShot
 * (c) 2012 Cedric Morin
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip("inc/config");
include_spip("lib/mandrill-api-php/src/Mandrill");
include_spip("inc/distant");

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
function &bulkmailer_mandrill_dist($to_send,$options=array()){
	static $config = null;
	static $mailer_defaut;
	if (is_null($config)){
		$config = lire_config("mailshot/");
		$mailer_defaut = charger_fonction("defaut","bulkmailer");
	}

	// passer l'api-key de mailshot
	if (empty($options['mandrill_api_key'])) {
		$options['mandrill_api_key'] = $config['mandrill_api_key'];
	}

	// on ecrase le smtp avec celui de la config
	$options['sender_class'] = "FacteurBulkMandrill";

	return $mailer_defaut($to_send,$options);
}

/**
 * Prendre en charge le webhook mandrill
 *
 * @param $arg
 */
function bulkmailer_mandrill_webhook_dist($arg){

	if ($_SERVER['REQUEST_METHOD'] == 'HEAD'){
		http_response_code(200);
		exit;
	}

	$events = _request('mandrill_events');
	spip_log("bulkmailer_mandrill_webhook_dist $events","mailshot_feedback");

	$events = json_decode($events, true);

	#spip_log("bulkmailer_mandrill_webhook_dist ".var_export($events,true),"mailshot_feedback");

	foreach ($events as $event){
		$quoi = $event['event'];
		if ($quoi=="open") $quoi="read"; // open chez mandrill, read ici
		if ($quoi=="click") $quoi="clic"; // click chez mandrill, clic ici

		$email = $event['msg']['email'];
		$tags = $event['msg']['tags'];
		if (count($tags)){
			$tracking_id = end($tags);
			$tracking_id = explode('/#',$tracking_id);
			if (reset($tracking_id)==protocole_implicite($GLOBALS['meta']['adresse_site'])){
				$tracking_id = end($tracking_id);
				spip_log("tracking $quoi $email $tracking_id",'mailshot');
				// appeler l'api webhook mailshot
				$feedback = charger_fonction("feedback","newsletter");
				$feedback("$quoi|".$event['event'],$email,$tracking_id);
			}
		}
	}
}


/**
 * Initialiser mandrill : declarer un webhook pour recuperer les retours sur bounce, reject, open, clic....
 *
 * @param int $id_mailshot
 * @return bool
 */
function bulkmailer_mandrill_init_dist($id_mailshot=0){
	$mailer_factory = charger_fonction('mandrill','bulkmailer');
	$facteur = $mailer_factory([]);

	$facteur->initWebHooks('newsletter');

	return true;
}
