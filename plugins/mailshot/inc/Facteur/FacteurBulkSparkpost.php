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
 * Utilise l'API Sparkpost
 */
class FacteurBulkSparkpost extends FacteurApi {

	/**
	 * Renseigne par newsletter/send avant l'envoi
	 */

	protected $message = array(
		'options' => array(
			'open_tracking' => false,
			'clic_tracking' => false,
		),
		'campaign_id' => '',
		#'return_path' => '',// do not provide if empty
		#'metadata' => array(), // not used here, need to be a JSON object, fail if empty
		#'substitution_data' => array(), // not used here, need to be a JSON object, fail if empty
		'recipients' => array(
			#array('address' => array('email'=>'','name'=>''))
		),
		'content' => array(
			'from' => array('email'=>'','name'=>''),
			'subject' => '',
			#'reply_to' => '', // do not provide if empty
			'headers' => array(),
			'text' => '',
			'html' => '',
		)
	);

	protected static $logName = 'bulksparkpost';

	/**
	 * utilise $this->send_options options d'envoi
	 *     string tracking_id
	 * @return bool
	 */
	public function Send(){

		$this->message['content']['html'] = $this->Body;
		$this->message['content']['text'] = $this->AltBody;
		$this->message['content']['subject'] = $this->Subject;
		$this->message['content']['from']['email'] = $this->From;
		$this->message['content']['from']['name'] = $this->FromName;
		if (empty($this->AltBody) && $this->ContentType === static::CONTENT_TYPE_PLAINTEXT) {
			$this->message['content']['html'] = '';
			$this->message['content']['text'] = $this->Body;
		}

		foreach (['to', 'cc', 'bcc'] as $q) {
			foreach ($this->recipients[$q] as $a){
				$this->message['recipients'][] = array('address' => $a);
			}
		}

		if ($this->recipients['reply-to']
		  and $replyto = end($this->recipients['reply-to'])){
			$this->message['content']['reply_to'] = $this->formatEmailDest($replyto);
		}

		foreach ($this->headers as $name => $value){
			$this->message['content']['headers'][$name] = $value;
		}


		// ajouter le tracking_id en tag, pour retrouver le message apres webhook
		if (!empty($this->send_options['tracking_id'])){
			$this->message['options']['open_tracking'] = true;
			$this->message['options']['clic_tracking'] = true;
			// prefixer le tracking par l'url du site (coupée à 45 caractères car campaign_id accepte 64 max) pour ne pas melanger les feedbacks
			$this->message['campaign_id'] = substr(protocole_implicite($GLOBALS['meta']['adresse_site']), 0, 45) . "/#" . $this->send_options['tracking_id'];
		}

		try {
			$response = sparkpost_api_call('transmissions', $this->message);
		} catch (Exception $e) {
			$this->SetError($e->getMessage());
			return false;
		}

		$this->log("Send() resultat:" . json_encode($response), _LOG_DEBUG);


		// statut d'erreur au premier niveau ?
		if (isset($response['errors'])){
			$err = "";
			foreach ($response['errors'] as $e){
				$err .= $e['code'] . ' ' . $e['message'] . "\n";
			}
			$dump = $this->message;
			$dump['content']['html'] = '...';
			$dump['content']['text'] = '...';
			$this->SetError($err . json_encode($dump));
			return false;
		}

		// sinon regarder le status du premier mail envoye (le to)
		/*
		{
		  "results": {
			"total_rejected_recipients": 0,
			"total_accepted_recipients": 1,
			"id": "11668787484950529"
		  }
		}
		 */
		if (isset($response['results'])){
			if ($response['results']['total_accepted_recipients']>=1){
				return true;
			}
			if ($response['results']['total_rejected_recipients']>=1){
				$this->SetError("rejected");
				return false;
			}
		}

		// ici on ne sait pas ce qu'il s'est passe !
		$this->SetError("??????" . json_encode($response));
		$this->log("Send() resultat inatendu : " . json_encode($response), _LOG_ERREUR);
		return false;
	}

}
