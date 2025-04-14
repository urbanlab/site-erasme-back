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

use SPIP\Facteur\FacteurSendinblue as FacteurSendinblue;

if (!defined("_ECRIRE_INC_VERSION")){
	return;
}


include_spip('inc/Facteur/FacteurSendinblue');


/**
 * Utilise l'API SendInBlue
 */
class FacteurBulkSendinblue extends FacteurSendinblue {

	/**
	 * Renseigne par newsletter/send avant l'envoi
	 */
	public $send_options = array();

	protected static $logName = 'bulksendinblue';

	/**
	 * utilise $this->send_options options d'envoi
	 *     string tracking_id
	 * @return bool|array
	 */
	public function Send(){

		// ajouter le tracking_id en tag, pour retrouver le message apres webhook
		if (!empty($this->send_options['tracking_id'])){
			$this->trackingId = $this->send_options['tracking_id'];
		}

		$this->important = false;
		return parent::Send();
	}

}
