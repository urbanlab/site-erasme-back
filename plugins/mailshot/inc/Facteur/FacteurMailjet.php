<?php
/**
 * Plugin Mailshot
 * Extension du Plugin Facteur 4
 * (c) 2009-2019 Collectif SPIP
 * Distribue sous licence GPL
 *
 * @package SPIP\Facteur\FacteurMailjet
 */

namespace SPIP\Facteur;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use SPIP\Mailshot\Api\Mailjetv3 as Mailjet;

if (!defined("_ECRIRE_INC_VERSION")){
	return;
}



include_spip('inc/Facteur/FacteurApi');


/**
 * Utilise l'API REST en v3
 */
class FacteurMailjet extends FacteurApi {

	protected static $logName = 'mailjet';

	protected $api_version = "v3/";
	protected $message = array(
		'FromEmail' => '',
		'FromName' => '',
		'Subject' => '',
		'Text-part' => '',
		'Html-part' => '',
		//'Mj-campaign' => '',
		//'Mj-deduplicatecampaign' => 1,
		//'Mj-CustomID' => '',
		'Headers' => array(//'Reply-To' => 'copilot@mailjet.com',
		),
		'Attachments' => array(// {"Content-type":"text/plain","Filename":"test.txt","content":"VGhpcyBpcyB5b3VyIGF0dGFjaGVkIGZpbGUhISEK"}]
		),
		'Inline_attachments' => array(// {"Content-type":"text/plain","Filename":"test.txt","content":"VGhpcyBpcyB5b3VyIGF0dGFjaGVkIGZpbGUhISEK"}]
		),

	);

	// pour le tracking des campagne
	protected $trackingId;

	protected $apiVersion = 3;
	protected $apiKey;
	protected $apiSecretKey;

	public static function newMailjetApi($version, $key, $secretKey) {
		switch ($version) {
			case 3:
			default:
				include_spip('lib/mailjet-api-php/mailjet-3');
				$mj = new Mailjet($key, $secretKey);
		}
		$mj->debug = 0;

		return $mj;
	}

	/**
	 * Facteur constructor.
	 * @param array $options
	 * @throws Exception
	 */
	public function __construct($options = array()){
		parent::__construct($options);
		$this->mailer = 'mailjet';

		if (!empty($options['mailjet_api_version'])){
			$this->apiVersion = $options['mailjet_api_version'];
		}
		if (!empty($options['mailjet_api_key'])){
			$this->apiKey = $options['mailjet_api_key'];
		}
		if (!empty($options['mailjet_api_version'])){
			$this->apiSecretKey = $options['mailjet_secret_key'];
		}
		if (!empty($options['tracking_id'])){
			$this->trackingId = $options['tracking_id'];
		}
	}

	/**
	 * Auto-configuration du mailer si besoin
	 * (rien a faire ici dans le cas par defaut)
	 * @return bool
	 */
	public function configure(){
		parent::configure();
		$this->addAuthorizedSender($this->From);
		return true;
	}

	/**
	 * @return Mailjet
	 */
	public function &getMailjetAPI(){
		static $mj = null;
		if (is_null($mj)){
			$mj = self::newMailjetApi($this->apiVersion, $this->apiKey, $this->apiSecretKey);
		}
		return $mj;
	}

	/**
	 * Initialiser les webhooks pour les newsletter
	 * @param string $quoi important|newsletter
	 * @return void
	 */
	public function initWebHooks($quoi){
		$this->log("initWebHooks() $quoi",_LOG_DEBUG);

		// son webhook
		include_spip('inc/mailshot');
		$url_webhook = mailshot_url_webhook($this->mailer);
		if ($url_webhook) {
			$mj = $this->getMailjetAPI();

			$params = array();
			$res = $mj->eventcallbackurl($params);
			#$this->log($res,_LOG_DEBUG);
			$events = array("open", "click", "bounce", "spam", "blocked", "unsub");

			if (isset($res['Count'])
			  AND $res['Count']>0
				AND isset($res['Data'])
				AND $res['Data']){

				foreach($res['Data'] as $eventCallback){
					if (in_array($eventCallback['EventType'],$events)){
						if ($eventCallback['Url'] === $url_webhook and $eventCallback['Status'] === 'alive'){
							// OK pour cet event, rien a faire
							$events = array_diff($events,array($eventCallback['EventType']));
							$webhookId = $eventCallback['ID'];
						}
						else {
							// il faut supprimer cette callback qui n'est pas sur la bonne URL
							// ou qui est 'dead'
							// et on la rajoutera ensuite avec la bonne URL (en dessous) et le status alive par defaut
							$params = array(
								'path' => $eventCallback['ID'],
								'method' => 'DELETE',
							);
							$this->log("initWebHooks(): deleteWebhook #" .$eventCallback['ID'], _LOG_DEBUG);
							$mj->eventcallbackurl($params);
						}
					}
				}
			}

			// donc on a pas tous les webhook pour ce site, on les ajoute
			if (count($events)){
				foreach($events as $event){
					$params = array(
						'data' => array(
							'EventType' => $event,
							'Url' => $url_webhook,
							'Version' => 2,
						),
					);
					$this->log("initWebHooks(): append Webhook : " . json_encode($params), _LOG_DEBUG);
					$res = $mj->eventcallbackurl($params);
					#$this->log($res,_LOG_DEBUG);
				}
			}
			else {
				$this->log("initWebHooks(): Rien a faire : webhooks complets", _LOG_DEBUG);
			}
		} else {
			$this->log("initWebHooks ignore",_LOG_DEBUG);
		}

		return true;
	}

	/**
	 * Verifier le status des messages ids envoyes
	 * a implementer par chaque API
	 * @param $apiCredentials
	 * @param $ids
	 * @return array[]
	 */
	public static function checkMessagesSentStatus($apiCredentials, $ids) {
		$failed = [];
		$recheck = [];
		if ($ids
			and $mj = static::newMailjetApi($apiCredentials['version'], $apiCredentials['key'], $apiCredentials['secretKey'])){
			foreach ($ids as $id){

				static::logDebug("checkMessagesSentStatus: check message id $id", 0);

				$status = $mj->message(['path' => $id]);
				if (!$status){
					$recheck[] = $id;
				}
				else {
					if (empty($status['Count']) or empty($status['Data'])){
						static::logDebug("checkMessagesSentStatus: FAIL message $id " . json_encode($status), 0);
						$failed[] = $id;
					}
					else {
						foreach ($status['Data'] as $message){
							switch (strtolower($message['Status'])) {
								case 'unknown':
								case 'queued':
								case 'deferred':
									static::logDebug("checkMessagesSentStatus: RECHECK message $id " . json_encode($message), 0);
									$recheck[] = $id;
									break;

								case 'bounce':
								case 'spam':
								case 'unsub':
								case 'blocked':
								case 'hardbounced':
								case 'softbounced':
									$failed[] = $id;
									static::logDebug("checkMessagesSentStatus: FAIL message $id " . json_encode($message), 0);
									break;

								case 'sent':
								case 'opened':
								case 'clicked':
								default:
									static::logDebug("checkMessagesSentStatus: OK message $id " . json_encode($message), 0);
									break;
							}
						}
					}
				}
				if (count($failed)){
					break;
				}
			}
		}

		return ['failed' => $failed, 'recheck' => $recheck];
	}

	/**
	 * Lister les api Credentials pour le futur check de message livre
	 * a implementer par chaque API
	 * @return array
	 */
	protected function getApiCredentials() {
		$apiCredentials = ['version' => $this->apiVersion, 'key' => $this->apiKey, 'secretKey' => $this->apiSecretKey];
		return $apiCredentials;
	}

	/**
	 * Extraire les messages ID de la reponse de l'API lors de l'envoi
	 * a implementer par chaque API
	 * @param $res
	 * @return array
	 */
	protected function getMessageIdsFromResult($res) {
		$message_ids = [];
		#$this->log("getMessageIdsFromResult MailJet " . var_export($res, true), _LOG_DEBUG);
		#$this->log("getMessageIdsFromResult MailJet " . var_export($this->recipients['to'], true), _LOG_DEBUG);
		if (!empty($res['Sent']) and !empty($this->recipients['to'])){
			$all_dests = array_column($this->recipients['to'], 'email');
			foreach ($res['Sent'] as $message){
				if (!empty($message['Email']) and !empty($message['MessageID'])){
					if (in_array($message['Email'], $all_dests)){
						$message_ids[] = $message['MessageID'];
					}
				}
			}
		}
		return $message_ids;
	}


	/**
	 * Verifier si un email d'envoi est dans la liste des senders mailjet
	 * et sinon l'ajoute
	 *
	 * @param string $sender_email
	 * @param bool $force
	 * @return bool
	 */
	public function addAuthorizedSender($sender_email, $force = false){

		$status = $this->readSenderStatus($sender_email);

		if ($status=="active"){
			return $status;
		} // active
		if ($status AND !$force){
			return $status;
		} // pending

		// si le sender n'est pas dans la liste ou en attente
		$mj = $this->getMailjetAPI();

		$params = array(
			'data' => array('Email' => $sender_email),
		);
		$res = $mj->sender($params);

		return $this->readSenderStatus($sender_email);
	}

	/**
	 * Lire le status d'un sender chez mailjet
	 * @param string $sender_email
	 * @return bool|string
	 */
	protected function readSenderStatus($sender_email){

		$mj = $this->getMailjetAPI();
		$params = array(
			'filters' => array('Email' => $sender_email),
		);
		$res = (array)$mj->sender($params);
		if (!isset($res['Count'])){
			return null;
		}
		if (isset($res['Data'])){
			foreach ($res['Data'] as $sender){
				if ($sender['Email']==$sender_email){
					if (in_array($sender['Status'], array('Active', 'Inactive'))){
						return strtolower($sender['Status']);
					}
				}
			}
		}

		return false;
	}


	/**
	 * @return bool|array
	 * @throws \Exception
	 */
	public function Send(){
		$this->forceFromIfNeeded();

		// est-ce un message au format texte uniquement ?
		if (empty($this->AltBody) && $this->ContentType === static::CONTENT_TYPE_PLAINTEXT) {
			$this->message['Html-part'] = '';
			$this->message['Text-part'] = $this->Body;
		} else {
			$this->message['Html-part'] = $this->Body;
			$this->message['Text-part'] = $this->AltBody;
		}

		$this->message['Subject'] = $this->Subject;
		$this->message['FromEmail'] = $this->From;
		$this->message['FromName'] = $this->FromName;

		if ($this->recipients['reply-to']
		  and $replyto = end($this->recipients['reply-to'])){
			$this->message['Headers']['Reply-To'] = $this->formatEmailDest($replyto);
		}

		foreach ($this->headers as $name => $value){
			$this->message['Headers'][$name] = $value;
		}

		/**
		 * $this->attachment[] = [
		 * 0 => $path,
		 * 1 => $filename,
		 * 2 => $name,
		 * 3 => $encoding,
		 * 4 => $type,
		 * 5 => false, //isStringAttachment
		 * 6 => $disposition,
		 * 7 => $name,
		 * ];
		 */
		if (count($this->attachment)){
			$inline_attachements = [];
			$attachments = [];
			foreach ($this->attachment as $attachment){
				$bString = $attachment[5];
				if ($bString){
					$string = $attachment[0];
				} else {
					$path = $attachment[0];
					$string = file_get_contents($path);
				}
				$string = base64_encode($string);

				if ($attachment[6]==='inline'){
					$inline_attachements[] = array(
						"Content-type" => $attachment[4],
						"Filename" => $attachment[7], // cid
						"content" => $string,
					);
				} else {
					$attachments[] = array(
						"Content-type" => $attachment[4],
						"Filename" => $attachment[2],
						"content" => $string
					);
				}
				// {"Content-type":"text/plain","Filename":"test.txt","content":"VGhpcyBpcyB5b3VyIGF0dGFjaGVkIGZpbGUhISEK"}]
			}
			$this->message['Attachments'] = $attachments;
			$this->message['Inline_attachments'] = $inline_attachements;
		}

		foreach (['to' => 'To', 'cc' => 'Cc', 'bcc' => 'Bcc'] as $q => $dest_type){
			if (!empty($this->recipients[$q])){
				$dests = array();
				foreach ($this->recipients[$q] as $dest){
					$dests[] = $this->formatEmailDest($dest);
				}
				$this->message[$dest_type] = implode(',', $dests);
			}
		}


		// ajouter le trackingId en tag, pour retrouver le message apres webhook
		if (!empty($this->trackingId)){
			// prefixer le tracking par l'url du site pour ne pas melanger les feedbacks
			$this->message['Mj-campaign'] = $this->campaignName($this->trackingId);
			$this->message['Mj-deduplicatecampaign'] = 1;
		}


		// pas de valeur vide dans le message
		foreach (array_keys($this->message) as $k){
			if (empty($this->message[$k])){
				unset($this->message[$k]);
			}
		}

		/*
		$trace = $this->message;
		unset($trace['Html-part']);
		unset($trace['Text-part']);
		if (!empty($trace['Attachments'])) {
			$trace['Attachments'] = "Array(".count($trace['Attachments']) .")";
		}
		if (!empty($trace['Inline_attachments'])) {
			$trace['Inline_attachments'] = "Array(".count($trace['Inline_attachments']) .")";
		}
		$this->log($trace, _LOG_DEBUG);
		*/

		$mj = $this->getMailjetAPI();
		$res = $mj->send(array('data' => $this->message));
		if (!$res){
			$this->SetError($mj->_error);
			if ($this->exceptions){
				throw new \Exception($mj->_error);
			}
			return $this->sendAlertIfNeeded(false);
		}

		/*
		{
		    "ErrorInfo": "Bad Request",
		    "ErrorMessage": "Unknown resource: \"contacts\"",
		    "StatusCode": 400
		}
		*/

		// statut d'erreur au premier niveau ?
		if (isset($res['StatusCode'])
			AND intval($res['StatusCode']/100)>2){

			$error = "status " . $res['StatusCode'] . " - " . $res['ErrorInfo'] . ": " . $res['ErrorMessage'];
			$this->SetError($error);
			if ($this->exceptions){
				throw new \Exception($error);
			}
			return $this->sendAlertIfNeeded(false);
		}

		// { "Sent" : [{ "Email" : "cedric@yterium.com", "MessageID" : 19140330729428381 }] }
		if (isset($res['Sent']) AND count($res['Sent'])){
			return $this->sendAlertIfNeeded($res);
		}

		// les autres type de reponse sont non documentees. On essaye au hasard?
		if (isset($res['Queued']) AND count($res['Queued'])){
			return $this->sendAlertIfNeeded($res);
		}
		if (isset($res['Invalid']) AND count($res['Invalid'])){
			$this->SetError($error = "invalid");
			if ($this->exceptions){
				throw new \Exception($error);
			}
			return $this->sendAlertIfNeeded(false);
		}
		if (isset($res['Rejected']) AND count($res['Rejected'])){
			$this->SetError($error = "rejected");
			if ($this->exceptions){
				throw new \Exception($error);
			}
			return $this->sendAlertIfNeeded(false);
		}

		// Erreur inconnue
		$this->SetError("mailjetERROR " . var_export($res, true));
		$this->log($error = "mailjet/send resultat inatendu : " . json_encode($res), _LOG_ERREUR);
		if ($this->exceptions){
			throw new \Exception($error);
		}
		return $this->sendAlertIfNeeded(false);
	}

}
