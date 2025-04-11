<?php
/**
 * Plugin MailShot
 * (c) 2012 Cedric Morin
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')){
	return;
}
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
function &bulkmailer_sendinblue_dist($to_send, $options = array()){
	static $config = null;
	static $mailer_defaut;
	if (is_null($config)){
		$config = lire_config("mailshot/");
		$mailer_defaut = charger_fonction("defaut", "bulkmailer");
	}

	// on utilise une surcharge pour utiliser l'API http
	$options['sender_class'] = "FacteurBulkSendinblue";
	// passer l'api-key de mailshot
	if (empty($options['sendinblue_api_key'])) {
		$options['sendinblue_api_key'] = $config['sendinblue_api_key'];
	}
	$facteur = $mailer_defaut($to_send, $options);
	return $facteur;
}


/**
 * Configurer sendinblue : declarer le sender si besoin
 * appele depuis traiter() de formulaire_configurer_mailshot
 * @param $res
 */
function bulkmailer_sendinblue_config_dist(&$res){
	$sender_mail = "";

	$mailer_factory = charger_fonction('sendinblue','bulkmailer');
	$facteur = $mailer_factory([]);
	$facteur->configure();
}

/**
 * Initialiser sendinblue : declarer un eventcallbackurl pour recuperer les retours sur bounce, reject, open, clic....
 *
 * @param int $id_mailshot
 * @return bool
 */
function bulkmailer_sendinblue_init_dist($id_mailshot=0){

	spip_log("bulkmailer_sendinblue_init_dist $id_mailshot","mailshot");

	$mailer_factory = charger_fonction('sendinblue','bulkmailer');
	$facteur = $mailer_factory([]);

	// si il y a un envoyeur specifique, verifier qu'il est dans les senders
	if ($id_mailshot
		and $mailshot = sql_fetsel('*', 'spip_mailshots', 'id_mailshot=' . intval($id_mailshot))) {
		if (!empty($mailshot['from_email'])) {
			$facteur->addAuthorizedSender($mailshot['from_email'], $mailshot['from_name'], true);
		}
	}

	$facteur->initWebHooks('newsletter');
	return true;
}


/**
 * Prendre en charge le webhook sendinblue
 *
 * @param $arg
 */
function bulkmailer_sendinblue_webhook_dist($arg){

	if ($_SERVER['REQUEST_METHOD'] == 'HEAD'){
		http_response_code(200);
		exit;
	}

	$data = file_get_contents('php://input');
	spip_log("bulkmailer_sendinblue_webhook_dist $data","mailshot_feedback");

	if (!$data OR !$events = json_decode($data, true)){
		http_response_code(403);
		exit;
	}

	// si un seul event, on le met dans un tableau pour tout traiter de la meme facon
	if (isset($events['event']) and isset($events['message-id'])){
		$events = [$events];
	}

	$important_mails = [];
	foreach($events as $event){
		// [
		// "request", "deferred", "delivered", "opened", "unique_opened", "click",
		// "soft_bounce", "hard_bounce", "complaint", "invalid_email", "blocked", "error", "unsubscribed"
		// ];

		// + delivered pour les mails importants

		$quoi = $event['event'];
		if (in_array($quoi, ["request", "deferred"])) {
			continue; // on ignore ces events qu'on a en principe pas demande
		}
		switch ($quoi) {
			case 'unique_opened':
			case 'opened':
				$quoi = 'read';
				break;
			case 'click':
				$quoi = 'clic';
				break;
			case 'blocked':
			case 'error':
			case 'invalid_email':
				$quoi = 'reject';
				break;
			case 'complaint':
				$quoi = 'spam';
				break;
			case 'unsubscribed':
				$quoi = 'unsub';
				break;
		}

		$email = $event['email'];
		$message_id = $event['message-id'];
		if (!empty($event['tags'])) {
			foreach ($event['tags'] as $tracking_id) {
				if (strpos($tracking_id, '/#') !==false) {
					$tracking_id = explode('/#',$tracking_id);
					if (reset($tracking_id)==protocole_implicite($GLOBALS['meta']['adresse_site'])){
						$tracking_id = end($tracking_id);
						spip_log("tracking $quoi $email $tracking_id - $message_id",'mailshot_feedback');
						if ($tracking_id === 'important') {
							if (empty($important_mails[$message_id])) {
								$important_mails[$message_id] = array();
							}
							$important_mails[$message_id][] = $quoi;
						}
						// ignorer le delivered sinon, il ne sert que pour les mails importants
						elseif ($quoi !== "delivered") {
							// appeler l'api webhook mailshot avec le raw_feedback en plus pour la trace en base
							$feedback = charger_fonction("feedback","newsletter");
							$feedback("$quoi|".$event['event'],$email,$tracking_id);
						}
					}
				}
			}
		}
	}

	if (!empty($important_mails)) {
		spip_log("Important mails tracked ".json_encode($important_mails),'mailshot_feedback' . _LOG_DEBUG);

		// charger le facteur transactionnel sendinblue (pas le bulk)
		include_spip('inc/facteur');
		$facteur = facteur_factory(['mailer' => 'sendinblue']);

		foreach ($important_mails as $message_id => $statuses) {
			$facteur->recordMessagesSentStatus($message_id, $statuses);
		}
	}

}

