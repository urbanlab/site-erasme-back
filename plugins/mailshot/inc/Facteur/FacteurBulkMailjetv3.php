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

use SPIP\Facteur\FacteurMailjet as FacteurMailjet;

if (!defined("_ECRIRE_INC_VERSION")){
	return;
}


include_spip('inc/Facteur/FacteurMailjet');


/**
 * Utilise l'API REST en v3
 * Class FacteurBulkMailjetv3
 */
class FacteurBulkMailjetv3 extends FacteurMailjet {

	/**
	 * Renseigne par newsletter/send avant l'envoi
	 */
	public $send_options = array();

	protected static $logName = 'bulkmailjet';

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
