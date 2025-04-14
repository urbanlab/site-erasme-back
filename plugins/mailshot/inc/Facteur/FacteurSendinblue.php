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

if (!defined("_ECRIRE_INC_VERSION")){
	return;
}


include_spip('inc/Facteur/FacteurApi');


/**
 * Utilise l'API SendInBlue
 */
class FacteurSendinblue extends FacteurApi {

	protected static $logName = 'sendinblue';

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
		$this->mailer = 'sendinblue';

		if (!empty($options['sendinblue_api_key'])){
			$this->apiKey = $options['sendinblue_api_key'];
		}
		if (!empty($options['tracking_id'])){
			$this->trackingId = $options['tracking_id'];
		}
		// on attends plus longtemps pour verifier qu'un email important a bien été envoyé
		$this->checkSentDelay = 120;
		$this->checkSentRecheckDelay = 600;
	}

	/**
	 * Auto-configuration du mailer si besoin
	 * (rien a faire ici dans le cas par defaut)
	 * @return bool
	 */
	public function configure(){
		parent::configure();
		$this->addAuthorizedSender($this->From, $this->FromName);
		return true;
	}

	public static function sendInBlueApiConfig($apiKey){
		static $sib_config = [];
		if (empty($sib_config[$apiKey])){
			include_spip('lib/brevo-apiv3-php/vendor/autoload');

			// Configure API key authorization: api-key
			$sib_config[$apiKey] = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
			// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
			// $config = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('api-key', 'Bearer');

			// Configure API key authorization: partner-key
			// $config = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('partner-key', 'YOUR_API_KEY');

			// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
			// $config = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('partner-key', 'Bearer');
		}

		return $sib_config[$apiKey];
	}


	/**
	 * Verifier si un email d'envoi est dans la liste des senders mailjet
	 * et sinon l'ajoute
	 *
	 * @param string $sender_email
	 * @param string $sender_name
	 * @param bool $force
	 * @return bool
	 */
	public function addAuthorizedSender($sender_email, $sender_name, $force = false){

		$config = static::sendInBlueApiConfig($this->apiKey);
		$apiInstance = new \Brevo\Client\Api\SendersApi(null, $config);
		try {
			$domain = explode('@', $sender_email);
			$domain = end($domain);
			$senders = $apiInstance->getSenders('', $domain);
		} catch (\Exception $e) {
			$this->log("Exception when calling SendersApi->getSenders : " . $e->getMessage(), _LOG_ERREUR);
			$senders = null;
		}

		if ($senders){
			#spip_log(var_export($senders->getSenders(), true), 'dbgmailshot');
			foreach ($senders->getSenders() as $sender){
				if ($sender->getEmail()===$sender_email and $sender->getName()===$sender_name){
					if (!$sender->getActive() and $force){
						// on le supprime pour le recreer et relancer un email de confirmation
						$this->log("addAuthorizedSender(): suppression sender $sender_email | $sender_name pour le recreer");
						try {
							$apiInstance->deleteSender($sender->getId());
						} catch (\Exception $e) {
							$this->log("Exception when calling SendersApi->deleteSender : " . $e->getMessage(), _LOG_ERREUR);
						}
					} else {
						// rien a faire
						$this->log("addAuthorizedSender(): $sender_email | $sender_name deja a jour, rien a faire");
						return true;
					}
				}
			}
		}

		$this->log("addAuthorizedSender(): $sender_email n'est pas un sender connu, on l'ajoute");

		$sender = new \Brevo\Client\Model\CreateSender(['email' => $sender_email, 'name' => $sender_name]);
		try {
			$result = $apiInstance->createSender($sender);
		} catch (\Exception $e) {
			$this->log("Exception when calling SendersApi->createSender : " . $e->getMessage(), _LOG_ERREUR);
			$result = null;
		}

		return true;
	}


	/**
	 * Initialiser les webhooks pour les mail important ou les newsletter
	 * @param string $quoi important|newsletter
	 * @return void
	 */
	public function initWebHooks($quoi) {
		// le webhook dont on a besoin
		include_spip('inc/mailshot');
		$url_webhook = mailshot_url_webhook($this->mailer);
		if ($url_webhook) {

			$webhook_events = [
				"opened",
				"uniqueOpened", // a priori redondant de opened pour nous, a confirmer ?
				"click",
				"softBounce",
				"hardBounce",
				"spam",
				"invalid",
				"blocked",
				//"error", // generer une erreur quand on essaye de mettre cet event ET si on le met depuis l'interface on a null a la place
				"unsubscribed",
			];

			// pour les mails important on veut simplement savoir si ils sont delivres, le reste c'est du bonus
			// on ajoute donc cet event si on en a besoin
			if ($quoi === 'important') {
				$webhook_events[] = 'delivered';
			}


			// lister les webhooks existants
			$config = static::sendInBlueApiConfig($this->apiKey);
			$apiInstance = new \Brevo\Client\Api\WebhooksApi(null, $config);
			$type = 'transactional';

			try {
				$result = $apiInstance->getWebhooks($type);
				//spip_log(var_export($result, true), 'dbgmailshot'. _LOG_DEBUG);
			} catch (\Exception $e) {
				$this->log("initWebHooks(): Exception when calling WebhooksApi->getWebhooks : " . $e->getMessage(), _LOG_ERREUR);
				$result = null;
			}

			$deja_events = [];
			if ($result) {
				$webhooks = $result->getWebhooks();
				//spip_log(var_export($webhooks, true), 'dbgmailshot'. _LOG_DEBUG);
				foreach ($webhooks as $webhook) {
					if ($webhook['url'] === $url_webhook) {
						$webhook_deja = $webhook;
					} else {
						// supprimer les autres webhooks car ils vont recevoir des feedbacks de notre emailing
						try {
							$apiInstance->deleteWebhook($webhook['id']);
							$this->log("initWebHooks(): WebhooksApi->deleteWebhook #" . $webhook['id'], _LOG_DEBUG);
						} catch (\Exception $e) {
							$this->log("initWebHooks(): Exception when calling WebhooksApi->deleteWebhook : " . $e->getMessage(), _LOG_ERREUR);
							$result = null;
						}
					}
				}
			}

			$webhook_todo = [
				'url' => $url_webhook,
				'description' => 'Webhook auto mailshot/SPIP',
				'events' => $webhook_events,
				'type' => 'transactional'
			];

			if ($webhook_deja) {
				$webhookId = $webhook_deja['id'];
				$missing_events = array_diff($webhook_events, $webhook_deja['events']);
				if ($missing_events) {
					$this->log("Missing events : " . implode(', ', $missing_events), _LOG_DEBUG);
					$webhook_todo['events'] = array_merge($webhook_todo['events'], $deja_events);
					$webhook_todo['events'] = array_filter($webhook_todo['events']);

					$updateWebhook = new \Brevo\Client\Model\UpdateWebhook($webhook_todo); // \Brevo\Client\Model\UpdateWebhook | Values to update a webhook

					try {
						$apiInstance->updateWebhook($webhookId, $updateWebhook);
						$this->log("initWebHooks(): WebhooksApi->updateWebhook #$webhookId", _LOG_DEBUG);
					} catch (\Exception $e) {
						$this->log("initWebHooks(): Exception when calling WebhooksApi->updateWebhook : " . $e->getMessage(), _LOG_ERREUR);
						$result = null;
					}
				} else {
					$this->log("initWebHooks(): Rien a faire : webhook #$webhookId complet", _LOG_DEBUG);
				}
			} else {
				$createWebhook = new \Brevo\Client\Model\CreateWebhook($webhook_todo); // \Brevo\Client\Model\CreateWebhook | Values to create a webhook

				try {
					$result = $apiInstance->createWebhook($createWebhook);
					$this->log("initWebHooks(): WebhooksApi->createWebhook : " . json_encode($webhook_todo), _LOG_DEBUG);
				} catch (\Exception $e) {
					$this->log("initWebHooks(): Exception when calling WebhooksApi->createWebhook : " . $e->getMessage(), _LOG_ERREUR);
					$result = null;
				}
			}
		} else {
			$this->log("initWebHooks(): ignore", _LOG_DEBUG);
		}
	}

	/**
	 * Verifier le status des messages ids envoyes
	 * a implementer par chaque API
	 * @param $apiCredentials
	 * @param $ids
	 * @return array[]
	 */
	public static function checkMessagesSentStatus($apiCredentials, $ids){
		$statuses_fail = ['soft_bounce', 'hard_bounce', 'reject', 'spam', 'unsub'];
		$statuses_ok = ['delivered', 'read', 'clic'];

		return static::checkMessagesSentStatusFromStatusFile('sendinblue', $ids, $statuses_ok, $statuses_fail);
	}


	/**
	 * Lister les api Credentials pour le futur check de message livre
	 * a implementer par chaque API
	 * @return array
	 */
	protected function getApiCredentials() {
		$apiCredentials = ['key' => $this->apiKey];
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
		if (!empty($res['messageIds'])){
			$message_ids = $res['messageIds'];
		}
		// pour chaque ID on va placer un fichier status vide pour dire qu'on attend une reponse
		foreach ($message_ids as $message_id) {
			$file_status = static::messageSendStatusFile($this->mailer, $message_id);
			@touch($file_status);
		}

		return $message_ids;
	}


	/**
	 * utilise $this->send_options options d'envoi
	 *     string tracking_id
	 * @return bool|array
	 */
	public function Send(){
		$this->forceFromIfNeeded();
		$config = static::sendInBlueApiConfig($this->apiKey);

		if (empty($this->FromName)) {
			$this->FromName = spip_ucfirst(explode('@', $this->From)[0]);
		}
		$data = [
			'sender' => new \Brevo\Client\Model\SendSmtpEmailSender(['email' => $this->From, 'name' => $this->FromName]),
			'to' => [],
			'bcc' => [],
			'cc' => [],
			'htmlContent' => $this->Body,
			'textContent' => $this->AltBody,
			'subject' => $this->Subject,
			'tags' => [],
		];
		if (empty($this->AltBody) && $this->ContentType === static::CONTENT_TYPE_PLAINTEXT) {
			$data['htmlContent'] = '';
			$data['textContent'] = $this->Body;
		}

		foreach ($this->recipients['to'] as $to){
			$data['to'][] = new \Brevo\Client\Model\SendSmtpEmailTo(array_filter($to));
		}
		foreach ($this->recipients['bcc'] as $bcc){
			$data['bcc'][] = new \Brevo\Client\Model\SendSmtpEmailBcc(array_filter($bcc));
		}
		foreach ($this->recipients['cc'] as $cc){
			$data['cc'][] = new \Brevo\Client\Model\SendSmtpEmailCc(array_filter($cc));
		}
		if ($this->recipients['reply-to']
		  and $replyto = end($this->recipients['reply-to'])){
			$data['replyTo'] = new \Brevo\Client\Model\SendSmtpEmailReplyTo(array_filter($replyto));
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
			$data['attachment'] = [];
			foreach ($this->attachment as $attachment){
				$bString = $attachment[5];
				if ($bString){
					$string = $attachment[0];
				} else {
					$path = $attachment[0];
					$string = file_get_contents($path);
				}
				$string = base64_encode($string);

				$data['attachment'][] = [
					'content' => $string,
					'name' => $attachment[2],
				];
			}
		}

		if (!empty($this->headers)){
			$data['headers'] = $this->headers;
		}

		// ajouter le tracking_id en tag, pour retrouver le message apres webhook
		if (!empty($this->trackingId)){
			// prefixer le tracking par l'url du site pour ne pas melanger les feedbacks
			$data['tags'][] = $this->campaignName($this->trackingId);
		}

		if ($this->important){
			// on init les webhook pour avoir un feedback du message
			$this->initWebHooks('important');
			// on tag les messages importants qui seront traites en consequence dans le feedback sendinblue
			$data['tags'][] = $this->campaignName('important');
		}

		// ne pas envoyer de valeurs vides pour ces cles
		foreach (['to', 'bcc', 'cc', 'tags'] as $k) {
			if (empty($data[$k])) {
				unset($data[$k]);
			}
		}
		$sendSmtpEmail = new \Brevo\Client\Model\SendSmtpEmail($data);

		$apiInstance = new \Brevo\Client\Api\TransactionalEmailsApi(null, $config);

		try {
			$result = $apiInstance->sendTransacEmailWithHttpInfo($sendSmtpEmail);
			list($response, $status_code, $headers) = $result;

			if ($trace = json_decode((string)$response,true)) {
				$trace = json_encode($trace);
			}
			else {
				$trace = var_export($response, true);
			}
			$this->log("Send() : result " . $trace, _LOG_DEBUG);

			// verifier un peu le resultat...
			if (intval($status_code/100)>2){

				$error = "Error status $status_code";
				$this->SetError($error);
				if ($this->exceptions){
					throw new \Exception($error);
				}
				return $this->sendAlertIfNeeded(false);
			}

			$ids = ($response->getMessageIds() ?: []);
			$ids[] = $response->getMessageId();
			$ids = array_filter($ids);
			$result = [
				'messageIds' => $ids,
				'to' => array_column($this->recipients['to'], 'email'),
				'cc' => array_column($this->recipients['cc'], 'email'),
				'bcc' => array_column($this->recipients['bcc'], 'email'),
			];

			return $this->sendAlertIfNeeded($result);

		} catch (\Exception $e) {
			$this->log("Send() : Exception when calling TransactionalEmailsApi->sendTransacEmail : " . $e->getMessage(), _LOG_ERREUR);
			$this->SetError("status " .$e->getMessage());
			if ($this->exceptions){
				throw $e;
			}
			return $this->sendAlertIfNeeded(false);
		}
	}
}
