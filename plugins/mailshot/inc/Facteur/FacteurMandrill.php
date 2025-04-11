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

use SPIP\Facteur\FacteurApi as FacteurApi;
use Mandrill as Mandrill;

if (!defined("_ECRIRE_INC_VERSION")){
	return;
}


include_spip('inc/Facteur/FacteurApi');
include_spip("lib/mandrill-api-php/src/Mandrill");


/**
 * Utilise l'API Mandrill
 */
class FacteurMandrill extends FacteurApi {
	protected static $logName = 'mandrill';

	protected $apiKey;

	// pour le tracking des campagne
	protected $trackingId;


	/**
	 * Facteur constructor.
	 * @param array $options
	 * @throws \Exception
	 */
	public function __construct($options = array()){
		parent::__construct($options);
		$this->mailer = 'mandrill';

		if (!empty($options['mandrill_api_key'])){
			$this->apiKey = $options['mandrill_api_key'];
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
		//$this->addAuthorizedSender($this->From, $this->FromName);
		return true;
	}

	/**
	 * Initialiser les webhooks pour les newsletter
	 * @param string $quoi newsletter
	 * @return bool
	 */
	public function initWebHooks($quoi = 'newsletter'){

		// son webhook
		include_spip('inc/mailshot');
		$url_webhook = mailshot_url_webhook($this->mailer);
		if ($url_webhook) {
			$mandrill = static::getMandrillAPI($this->apiKey);

			// recuperer les webhooks existants
			try {
				$list = $mandrill->webhooks->getList();
				$this->log("initWebHooks: list " . json_encode($list), _LOG_DEBUG);
			} catch (\Exception $e) {
				$this->log($e = "initWebHooks: Mandrill Exception " . $e->getMessage(), _LOG_ERREUR);
				return false;
			}

			$events = array("hard_bounce", "soft_bounce", "open", "click", "spam", "reject");
			if ($quoi === 'important') {
				$events[] = 'send';
			}

			// chercher si un webhook deja existant avec cette url, et si les events sont ok
			if (count($list)){
				foreach ($list as $l){
					if ($l['url'] == $url_webhook){
						$e = $l['events'];
						if (!count(array_diff($e, $events)) and !count(array_diff($events, $e))){
							$this->log("initWebHooks: Webhook OK rien a faire", _LOG_DEBUG);
							return true;
						}

						// la liste des events est non ok : supprimer ce webhook
						try {
							$mandrill->webhooks->delete($l['id']);
						} catch (\Exception $e) {
							$this->log($e = "initWebHooks: Mandrill Exception " . $e->getMessage(), _LOG_ERREUR);
							return false;
						}
					}
				}
			}

			// donc on a pas de webhook pour ce site, on l'ajoute
			if (count($events)){
				try {
					$mandrill->webhooks->add($url_webhook, $events);
					$this->log("initWebHooks: ajout Webhook events ".json_encode($events)." sur $url_webhook", _LOG_DEBUG);
				} catch (\Exception $e) {
					$this->log($e = "initWebHooks: Mandrill Exception " . $e->getMessage(), _LOG_ERREUR);
					return false;
				}
			}
		} else {
			$this->log("initWebHooks: Webhook ignore", _LOG_DEBUG);
		}

		return true;
	}

	/**
	 * @param string $apiKey
	 * @return SpipMandrill
	 */
	public static function getMandrillAPI($apiKey){
		$mandrill = new SpipMandrill($apiKey);
		return $mandrill;
	}

	/**
	 * Verifier le status des messages ids envoyes
	 * a implementer par chaque API
	 * @param $apiCredentials
	 * @param $ids
	 * @return array[]
	 */
	public static function checkMessagesSentStatus($apiCredentials, $ids){
		$statuses_fail = ['soft_bounce', 'hard_bounce', 'reject', 'spam', 'unsub', 'rejected', 'invalid'];
		$statuses_ok = ['sent', 'read', 'clic'];
		$res = static::checkMessagesSentStatusFromStatusFile('mandrill', $ids, $statuses_ok, $statuses_fail);
		if (empty($res['failed']) and !empty($res['recheck'])){
			$recheck = $res['recheck'];
			$mandrill = static::getMandrillAPI($apiCredentials['apiKey']);
			foreach ($recheck as $message_id){
				try {
					$status = $mandrill->call('messages/info', array('id' => $message_id));
					static::logDebug("checkMessagesSentStatus: messages/info id=$message_id : " . json_encode($status), _LOG_DEBUG);
					if (!empty($status['state'])){
						$file_status = self::messageSendStatusFile('mandrill', $message_id);
						if (in_array($status['state'], $statuses_fail)){
							@unlink($file_status);
							$res['failed'][] = $message_id;
							break;
						} elseif (in_array($status['state'], $statuses_ok)) {
							@unlink($file_status);
							$res['recheck'] = array_diff($res['recheck'], [$message_id]);
						}
					}
				} catch (\Exception $e) {
					static::logDebug("checkMessagesSentStatus: " . $e->getMessage() . " pour retrouver les infos du message id $message_id", _LOG_ERREUR);
				}
			}
		}
		return $res;
	}

	/**
	 * Lister les api Credentials pour le futur check de message livre
	 * a implementer par chaque API
	 * @return array
	 */
	protected function getApiCredentials(){
		return ['apiKey' => $this->apiKey];
	}

	/**
	 * Extraire les messages ID de la reponse de l'API lors de l'envoi
	 * a implementer par chaque API
	 * @param $res
	 * @return array
	 */
	protected function getMessageIdsFromResult($res){
		$ids = [];
		foreach ($res as $message){
			$ids[] = $message['_id'];
			if (!empty($message['status'])){
				$this->recordMessagesSentStatus($message['_id'], [$message['status']], true);
			}
		}
		return $ids;
	}

	/**
	 * utilise $this->send_options options d'envoi
	 *     string tracking_id
	 * @return bool
	 */
	public function Send(){
		$this->forceFromIfNeeded();

		/**
		 * Send a new transactional message through Mandrill
		 * @param struct $message the information on the message to send
		 *     - html string the full HTML content to be sent
		 *     - text string optional full text content to be sent
		 *     - subject string the message subject
		 *     - from_email string the sender email address.
		 *     - from_name string optional from name to be used
		 *     - to array an array of recipient information.
		 *         - to[] struct a single recipient's information.
		 *             - email string the email address of the recipient
		 *             - name string the optional display name to use for the recipient
		 *     - headers struct optional extra headers to add to the message (currently only Reply-To and X-* headers are allowed)
		 *     - track_opens boolean whether or not to turn on open tracking for the message
		 *     - track_clicks boolean whether or not to turn on click tracking for the message
		 *     - auto_text boolean whether or not to automatically generate a text part for messages that are not given text
		 *     - url_strip_qs boolean whether or not to strip the query string from URLs when aggregating tracked URL data
		 *     - preserve_recipients boolean whether or not to expose all recipients in to "To" header for each email
		 *     - bcc_address string an optional address to receive an exact copy of each recipient's email
		 *     - merge boolean whether to evaluate merge tags in the message. Will automatically be set to true if either merge_vars or global_merge_vars are provided.
		 *     - global_merge_vars array global merge variables to use for all recipients. You can override these per recipient.
		 *         - global_merge_vars[] struct a single global merge variable
		 *             - name string the global merge variable's name. Merge variable names are case-insensitive and may not start with _
		 *             - content string the global merge variable's content
		 *     - merge_vars array per-recipient merge variables, which override global merge variables with the same name.
		 *         - merge_vars[] struct per-recipient merge variables
		 *             - rcpt string the email address of the recipient that the merge variables should apply to
		 *             - vars array the recipient's merge variables
		 *                 - vars[] struct a single merge variable
		 *                     - name string the merge variable's name. Merge variable names are case-insensitive and may not start with _
		 *                     - content string the merge variable's content
		 *     - tags array an array of string to tag the message with.  Stats are accumulated using tags, though we only store the first 100 we see, so this should not be unique or change frequently.  Tags should be 50 characters or less.  Any tags starting with an underscore are reserved for internal use and will cause errors.
		 *         - tags[] string a single tag - must not start with an underscore
		 *     - google_analytics_domains array an array of strings indicating for which any matching URLs will automatically have Google Analytics parameters appended to their query string automatically.
		 *     - google_analytics_campaign array|string optional string indicating the value to set for the utm_campaign tracking parameter. If this isn't provided the email's from address will be used instead.
		 *     - metadata array metadata an associative array of user metadata. Mandrill will store this metadata and make it available for retrieval. In addition, you can select up to 10 metadata fields to index and make searchable using the Mandrill search api.
		 *     - recipient_metadata array Per-recipient metadata that will override the global values specified in the metadata parameter.
		 *         - recipient_metadata[] struct metadata for a single recipient
		 *             - rcpt string the email address of the recipient that the metadata is associated with
		 *             - values array an associated array containing the recipient's unique metadata. If a key exists in both the per-recipient metadata and the global metadata, the per-recipient metadata will be used.
		 *     - attachments array an array of supported attachments to add to the message
		 *         - attachments[] struct a single supported attachment
		 *             - type string the MIME type of the attachment - allowed types are text/*, image/*, and application/pdf
		 *             - name string the file name of the attachment
		 *             - content string the content of the attachment as a base64-encoded string
		 * @param boolean $async enable a background sending mode that is optimized for bulk sending. In async mode, messages/send will immediately return a status of "queued" for every recipient. To handle rejections when sending in async mode, set up a webhook for the 'reject' event. Defaults to false for messages with fewer than 100 recipients; messages with more than 100 recipients are always sent asynchronously, regardless of the value of async.
		 * @return array of structs for each recipient containing the key "email" with the email address and "status" as either "sent", "queued", or "rejected"
		 *     - return[] struct the sending results for a single recipient
		 *         - email string the email address of the recipient
		 *         - status string the sending status of the recipient - either "sent", "queued", "rejected", or "invalid"
		 */
		$message = array(
			'html' => $this->Body,
			'text' => $this->AltBody,
			'subject' => $this->Subject,
			'from_email' => $this->From,
			'from_name' => $this->FromName,
			'to' => array(),
			'headers' => array()
		);
		if (empty($this->AltBody) && $this->ContentType === static::CONTENT_TYPE_PLAINTEXT) {
			$message['html'] = '';
			$message['text'] = $this->Body;
		}

		$async = true;

		foreach ($this->recipients['to'] as $to){
			$message['to'][] = $to;
		}
		foreach ($this->recipients['cc'] as $cc){
			$cc['type'] = 'cc';
			$message['to'][] = $cc;
		}
		foreach ($this->recipients['bcc'] as $bcc){
			$cc['type'] = 'bcc';
			$message['to'][] = $bcc;
		}

		if ($this->recipients['reply-to']
			and $replyto = end($this->recipients['reply-to'])){
			$message['headers']['ReplyTo'] = $this->formatEmailDest($replyto);
		}

		foreach ($this->headers as $name => $value){
			$message['headers'][$name] = $value;
		}

		// ajouter le tracking_id en tag, pour retrouver le message apres webhook
		if ($this->trackingId){
			$message['track_opens'] = true;
			$message['track_clicks'] = true;
			// prefixer le tracking par l'url du site pour ne pas melanger les feedbacks
			$message['tags'][] = $this->campaignName($this->trackingId);
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
			$message['attachments'] = [];
			foreach ($this->attachment as $attachment){
				$bString = $attachment[5];
				if ($bString){
					$string = $attachment[0];
				} else {
					$path = $attachment[0];
					$string = file_get_contents($path);
				}
				$string = base64_encode($string);

				$message['attachments'][] = [
					'name' => $attachment[1],
					'content' => $string,
					'type' => $attachment[4],
				];
			}
		}

		// est-ce un message important ?
		if ($this->important){
			$message['important'] = true;
			$async = false;
		}

		$mandrill = static::getMandrillAPI($this->apiKey);

		try {
			$res = $mandrill->messages->send($message, $async);
		} catch (\Exception $e) {
			$this->SetError($e->getMessage());
			if ($this->exceptions){
				throw $e;
			}
			return $this->sendAlertIfNeeded(false);
		}

		$this->log("Send() resultat:" . json_encode($res), _LOG_DEBUG);

		// statut d'erreur au premier niveau ?
		if (isset($res['status'])){
			switch ($res['status']) {
				case 'error':
					$this->SetError($error = $res['name'] . ": " . $res['message']);
					if ($this->exceptions){
						throw new \Exception($error);
					}
					return $this->sendAlertIfNeeded(false);
					break;
				default:
					$this->SetError($error = "??????" . json_encode($res));
					if ($this->exceptions){
						throw new \Exception($error);
					}
					return $this->sendAlertIfNeeded(false);
					break;
			}
		}

		// sinon regarder le status du premier mail envoye (le to)
		// ici on ne gere qu'un destinataire
		$rmail = reset($res);
		switch ($rmail['status']) {
			case 'invalid':
			case 'rejected':
				$this->SetError($error = "Send status is " . _q($rmail['status']));
				// si on a Facteur 5+ pas la peine de reessayer un envoi
				if (method_exists($this, "setIsFinalTry")) {
					$this->setIsFinalTry(true);
				}
				if ($this->exceptions){
					throw new \Exception($error);
				}
				return $this->sendAlertIfNeeded(false);
				break;
			case "sent":
			case "queued":
				return $this->sendAlertIfNeeded($res);
				break;
		}

		// ici on ne sait pas ce qu'il s'est passe !
		$this->SetError($error = "??????" . json_encode($res));
		$this->log("Send() resultat inatendu : " . json_encode($res), _LOG_ERREUR);
		if ($this->exceptions){
			throw new \Exception($error);
		}
		return $this->sendAlertIfNeeded($res);
	}
}


/**
 * Prise en charge par recuperer_url quand curl pas dispo ou pas complet
 */
class SpipMandrill extends \Mandrill {
	public function __construct($apikey = null){
		parent::__construct($apikey);

		//WARNING: this would prevent curl from detecting a 'man in the middle' attack
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
	}

	public function call($url, $params){
		$params['key'] = $this->apikey;
		$paramsjson = json_encode($params);
		$response_body = "";
		if (!function_exists('curl_init')
			or @ini_get("safe_mode")=="On"
			or @ini_get("open_basedir")){

			spip_log("Appel de Mandrill par recuperer_url", "mandrill" . _LOG_DEBUG);
			// essayer avec les fonctions natives de SPIP
			// mais ne supportent pas forcement https si pas openssl
			include_spip('inc/distant');
			$response_body = recuperer_url($this->root . $url . '.json', ['datas' => $paramsjson]);
			$response_body = (isset($response_body['page']) ? $response_body['page'] : null);
			if (!$response_body){
				spip_log("Echec Appel de Mandrill par recuperer_url", "mandrill" . _LOG_ERREUR);
			}
		}

		if (!$response_body){
			return parent::call($url, $params);
		}

		$result = json_decode($response_body, true);
		if ($result===null){
			throw new \Mandrill_Error('We were unable to decode the JSON response from the Mandrill API: ' . $response_body);
		}

		return $result;
	}
}
