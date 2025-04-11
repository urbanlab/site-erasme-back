<?php
/**
 * Plugin Mailshot
 * Extension du Plugin Facteur 4
 * (c) 2009-2019 Collectif SPIP
 * Distribue sous licence GPL
 *
 * @package SPIP\Facteur\FacteurApi
 */

namespace SPIP\Facteur;

use SPIP\Facteur\FacteurMail as FacteurMail;

if (!defined("_ECRIRE_INC_VERSION")){
	return;
}


include_spip('inc/Facteur/FacteurMail');


function mailshotCheckMessagesSentStatus($args) {
	if ($args = unserialize(base64_decode($args))
	  and is_array($args)) {

		[$facteurClass, $apiCredentials, $ids, $sendFailFunction, $max_try, $recheck_delay] = $args;
		$max_try--;
		$ids_recheck = [];
		$ids_failed = [];

		$baseClass = explode('\\', $facteurClass);
		$baseClass = end($baseClass);
		include_spip("inc/Facteur/$baseClass");
		if (!empty($ids)) {
			$r = $facteurClass::checkMessagesSentStatus($apiCredentials, $ids);
			$ids_failed = $r['failed'] ?? [];
			$ids_recheck = $r['recheck'] ?? [];
		}

		if ($ids_failed
		  or ($ids_recheck and $max_try <= 0)) {
			$facteur_envoyer_alerte_fail = charger_fonction('facteur_envoyer_alerte_fail','inc');
			$facteur_envoyer_alerte_fail($sendFailFunction['function'], $sendFailFunction['args'], $sendFailFunction['include']);
		}
		elseif ($ids_recheck) {
			// on re-essaye dans $recheck_delay, 1 fois de moins au maximum
			$facteurClass::planCheckMessagesSent($recheck_delay, $apiCredentials, $ids, $sendFailFunction, $max_try);
		}

		// tout est bon, rien a faire on a fini
		$facteurClass::logDebug("mailshotCheckMessagesSentStatus: Fini", 0);
	}
	else {
		FacteurApi::logDebug("mailshotCheckMessagesSentStatus: argument \$args incomprehensible", 0);
	}
}

/**
 * Prerequis pour utiliser implementer l'envoi des mails via une API au lieu de SMTP
 * Class FacteurApi
 */
class FacteurApi extends FacteurMail {

	/**
	 * Renseigne par newsletter/send avant l'envoi
	 */
	public $send_options = array();

	protected static $logName = 'facteurapi';

	protected $checkSentDelay = 60;
	protected $checkSentRecheckDelay = 300;
	protected $checkSentMaxTry = 5;

	protected $recipients = [
		'to' => [/*['email' => '','name' => '']*/],
		'cc' => [/*['email' => '','name' => '']*/],
		'bcc' => [/*['email' => '','name' => '']*/],
		'reply-to' => [/*['email' => '','name' => '']*/],
	];
	protected $headers = [

	];

	/**
	 * Planifier une prochaine verification de message bien arrives
	 * @param int $delay
	 * @param array $apiCredentials
	 * @param array $ids
	 * @param array $sendFailFunction
	 * @param int $count
	 * @return void
	 */
	public static function planCheckMessagesSent($delay, $apiCredentials, $ids, $sendFailFunction, $max_try, $recheck_delay = null) {
		$include = "inc/Facteur/FacteurApi";
		$time = time() + $delay;
		static::logDebug("planCheckMessagesSent: ids " . implode(', ', $ids), 0);
		$facteurClass = get_called_class();

		if (is_null($recheck_delay)) {
			$recheck_delay = $delay;
		}
		// les arguments pouvent etre longs et embarquer du contenu complique a echapper, on encode
		$args = [$facteurClass, $apiCredentials, $ids, $sendFailFunction, $max_try, $recheck_delay];
		$c = base64_encode(serialize($args));
		job_queue_add('SPIP\\Facteur\\mailshotCheckMessagesSentStatus', "$facteurClass Important mail check", [$c], $include, false, $time);
	}

	/**
	 * Calculer le nom de fichier qui sert a stocker le status d'un message d'un id donne
	 * @param string $mailer
	 * @param string $message_id
	 * @return string
	 */
	public static function messageSendStatusFile($mailer, $message_id) {
		$dir = _DIR_TMP . $mailer . '_status/';
		$dir = static::checkDirSendStatusFile($dir);

		$file_status = $dir . md5($message_id) . '.json';
		return $file_status;
	}

	public static function checkDirSendStatusFile($dir) {
		static $cleaned = [];
		if (!is_dir($dir)) {
			$dir = sous_repertoire($dir);
		}
		if (empty($cleaned[$dir])) {
			$cleaned[$dir] = true;
			$t_old = strtotime("-1month");
			$files = glob($dir . '*.json');
			foreach ($files as $file) {
				if (($t = filemtime($file)) && $t < $t_old) {
					@unlink($file);
				}
			}
		}
		return $dir;
	}

	/**
	 * Enregistrer le status des messages ids envoyes si on a une verif asynchrone via webhook par exemple
	 * @param string $message_id
	 * @param array $statuses
	 * @param bool $force
	 * @return array[]
	 */
	public function recordMessagesSentStatus($message_id, $statuses, $force = false){
		$this->log("recordMessagesSentStatus() $message_id : ".implode(',', $statuses), _LOG_DEBUG);
		$file_status = self::messageSendStatusFile($this->mailer ?? 'facteur', $message_id);
		// il faut qu'un fichier status existe deja (cree vide lors de l'envoi)
		// sinon on ignore (evite de creer des tonnes de fichier a cause des feedbacks)
		if (file_exists($file_status)) {
			if ($previous = file_get_contents($file_status)
	          and $previous = json_decode($previous, true)) {
				$statuses = array_merge($previous, $statuses);
			}
			file_put_contents($file_status, json_encode($statuses));
		}
		elseif($force) {
			file_put_contents($file_status, json_encode($statuses));
		}
	}
	/**
	 * Verifier le status des messages ids envoyes
	 * a implementer par chaque API
	 * @param $apiCredentials
	 * @param string $mailer
	 * @param array $ids
	 * @param array $statuses_ok
	 * @param array $statuses_fail
	 * @return array[]
	 */
	public static function checkMessagesSentStatusFromStatusFile($mailer, $ids, $statuses_ok, $statuses_fail){
		$failed = [];
		$recheck = [];
		foreach ($ids as $message_id) {
			$file_status = self::messageSendStatusFile($mailer, $message_id);
			if (file_exists($file_status)) {
				  if ($statuses = file_get_contents($file_status)
				  and $statuses = json_decode($statuses, true)) {
					$maybe_ok = false;
					foreach ($statuses as $status) {
						if (in_array($status, $statuses_fail)) {
							// c'est un fail certain
							$failed[] = $message_id;
							static::logDebug("checkMessagesSentStatus: FAIL message $message_id status:$status", 0);
							@unlink($file_status);
							$maybe_ok = false;
							break;
						}
						elseif(in_array($status, $statuses_ok)) {
							// c'est peut etre un succes si pas d'autre status echec a cote
							$maybe_ok = $status;
						}
					}
					if ($maybe_ok) {
						static::logDebug("checkMessagesSentStatus: OK message $message_id status: $maybe_ok", 0);
						@unlink($file_status);
					}
				}
				else {
					static::logDebug("checkMessagesSentStatus: RECHECK message $message_id - no status", 0);
					$recheck[] = $message_id;
				}
			} else {
				static::logDebug("checkMessagesSentStatus: LOST message $mailer/$message_id - abandon ($file_status missing)", 0);
			}
		}

		return ['failed' => $failed, 'recheck' => $recheck];
	}


	/**
	 * Verifier le status des messages ids envoyes
	 * a implementer par chaque API
	 * @param $apiCredentials
	 * @param $ids
	 * @return array[]
	 */
	public static function checkMessagesSentStatus($apiCredentials, $ids) {
		return ['failed' => [], 'recheck' => []];
	}

	/**
	 * Lister les api Credentials pour le futur check de message livre
	 * a implementer par chaque API
	 * @return array
	 */
	protected function getApiCredentials() {
		return [];
	}

	/**
	 * Extraire les messages ID de la reponse de l'API lors de l'envoi
	 * a implementer par chaque API
	 * @param $res
	 * @return array
	 */
	protected function getMessageIdsFromResult($res) {
		return [];
	}

	/**
	 * Verifier si il faut envoyer le mail d'alerte
	 * @param mixed $res
	 * @return mixed
	 */
	protected function sendAlertIfNeeded($res) {
		$this->log("sendAlertIfNeeded " . json_encode($res), _LOG_DEBUG);
		$this->log("sendAlertIfNeeded Important: " . $this->important, _LOG_DEBUG);
		$this->log("sendAlertIfNeeded sendFailFunction: " . ($this->sendFailFunction ? 'OK':'NOT'), _LOG_DEBUG);
		// c'est un fail
		if ($res === false) {
			return parent::sendAlertIfNeeded($res);
		}
		// sinon chercher les ids des message a verifier un peu plus tard si c'est un message important
		if ($this->important and !empty($this->sendFailFunction)){
			$message_ids = $this->getMessageIdsFromResult($res);
			if ($message_ids){
				$this->log("Message important, on check les ids " . json_encode($message_ids), _LOG_DEBUG);
				// verifier ces ids dans 60s
				$apiCredentials = $this->getApiCredentials();
				static::planCheckMessagesSent($this->checkSentDelay, $apiCredentials, $message_ids, $this->sendFailFunction, $this->checkSentMaxTry, $this->checkSentRecheckDelay);
			}
			else {
				$this->log("Message important, rien à vérifier, tout est envoyé", _LOG_DEBUG);
			}
		}

		return $res;
	}


	protected function cleanAdress($address, $name = ''){
		$address = trim($address);
		$name = trim(preg_replace('/[\r\n]+/', ' ', $name)); //Strip breaks and trim
		if (!self::ValidateAddress($address)){
			$this->SetError('invalid_address' . ': ' . $address);
			return false;
		}
		return array('email' => $address, 'name' => $name);
	}

	/**
	 * Mettre en forme une addresse email
	 * @param $dest
	 * @return string
	 */
	protected function formatEmailDest($dest){
		$d = $dest['email'];
		if (!empty($dest['name'])){
			$name = $dest['name'];
			if (preg_match(",\W,", $name)){
				$name = '"' . $name . '"';
			}
			$d = $name . " <$d>";
		}
		return $d;
	}

	/**
	 * Clear all recipients
	 */
	public function clearAllRecipients(){
		$this->recipients['to'] = [];
		$this->recipients['cc'] = [];
		$this->recipients['bcc'] = [];
		$this->recipients['reply-to'] = [];
		parent::clearAllRecipients();
	}


	/**
	 * Adds a "To" address.
	 * @param string $address
	 * @param string $name
	 * @return boolean true on success, false if address already used
	 */
	public function AddAddress($address, $name = '', $index = 'to'){
		if ($a = $this->cleanAdress($address, $name)){
			$this->recipients[$index][] = $a;
			return true;
		}
		return false;
	}

	/**
	 * Adds a "Cc" address.
	 * Note: this function works with the SMTP mailer on win32, not with the "mail" mailer.
	 * @param string $address
	 * @param string $name
	 * @return boolean true on success, false if address already used
	 */
	public function AddCC($address, $name = ''){
		return $this->AddAddress($address, $name, 'cc');
	}

	/**
	 * Adds a "Bcc" address.
	 * Note: this function works with the SMTP mailer on win32, not with the "mail" mailer.
	 * @param string $address
	 * @param string $name
	 * @return boolean true on success, false if address already used
	 */
	public function AddBCC($address, $name = ''){
		return $this->AddAddress($address, $name, 'bcc');
	}

	/**
	 * Adds a "Reply-to" address.
	 * @param string $address
	 * @param string $name
	 * @return boolean
	 */
	public function AddReplyTo($address, $name = ''){
		return $this->AddAddress($address, $name, 'reply-to');
	}

	/**
	 * Adds a custom header.
	 * @access public
	 * @return void
	 */
	public function AddCustomHeader($name, $value = null){
		if ($value===null){
			// Value passed in as name:value
			list($name, $value) = explode(':', $name, 2);
		}
		$this->headers[trim($name)] = trim($value);
	}


	/**
	 * Ne sert pas, sauf aux logs internes
	 * @return string|void
	 */
	public function CreateHeader(){
		$header = "";

		$header .= "Date: " . date('Y-m-d H:i:s') . "\n";

		$from = $this->formatEmailDest(['email' => $this->From, 'name' => $this->FromName]);
		$header .= "From: $from\n";

		foreach (['To' => 'to', 'Cc' => 'cc', 'Bcc' => 'bcc'] as $H => $dest_type){
			if (!empty($this->recipients[$dest_type]) and count($this->recipients[$dest_type])){
				$dests = [];
				foreach ($this->recipients[$dest_type] as $dest){
					$dests[] = $this->formatEmailDest($dest);
				}
				$header .= "$H: " . implode(',', $dests) . "\n";
			}
		}

		if (!empty($this->recipients['reply-to'])) {
			$dest = end($this->recipients['reply-to']);
			$header .= "Reply-To: " . $this->formatEmailDest($dest) . "\n";
		}

		if (!empty($this->headers)){
			foreach ($this->headers as $k => $h){
				$header .= "$k: $h\n";
			}
		}

		return $header;
	}

	protected function campaignName($trackingId) {
		return protocole_implicite($GLOBALS['meta']['adresse_site']) . "/#" . $trackingId;
	}
}
