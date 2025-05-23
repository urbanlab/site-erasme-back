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
 *   string sender_class : permet de specifier une autre class que "Facteur" (surcharges)
 * @return null|SPIP\Facteur\FacteurMail
 */
function &bulkmailer_defaut_dist($to_send,$options=array()){
	static $fail = null;
	static $defaut = null;

	if (!isset($options['smtp'])
	  and !isset($options['sender_class'])) {
		include_spip('inc/mailshot');
		if (!mailshot_is_facteur_default_config_allowed()){
			spip_log("Pas de SMTP configure et envoi par mail() refuse pour le bulk","mailshot"._LOG_ERREUR);
			return $fail;
		}
	}

	if (is_null($defaut)) {
		$config = lire_config("mailshot/");

		$defaut = array(
			'filtre_images' => false,
			'filtre_iso_8859' => false, // le passage en iso fait foirer les envois propres par smtp et mandrill
		);

		// envoyeur
		if (($config['adresse_envoi'] ?? '') === 'oui'){
			$defaut['adresse_envoi_nom'] = $config['adresse_envoi_nom'];
			$defaut['adresse_envoi_email'] = $config['adresse_envoi_email'];
		}
		else {
			include_spip('inc/facteur');
			$facteur = facteur_factory();
			$defaut['adresse_envoi_email'] = $facteur->From;
			$defaut['adresse_envoi_nom'] = $facteur->FromName;
		}
	}

	$options = array_merge($defaut,$options);

	// regler le smtp au format facteur
	if (isset($options['smtp'])){
		foreach (array('host','port','auth','username','password','secure','errorsto') as $quoi){
			$options['smtp_'.$quoi] = (isset($options['smtp'][$quoi])?$options['smtp'][$quoi]:'');
		}
		$options['smtp_sender'] = $options['smtp_errorsto'];
		$options['mailer'] = 'smtp';
	}

	// desactiver les cc&bcc automatique eventuel de facteur
	if (!isset($options['bcc']))
		$options['bcc'] = '';
	if (!isset($options['cc']))
		$options['cc'] = '';

	$facteur = null;
	if (!empty($options['sender_class'])){
		$sender_class = $options['sender_class'];
		if (include_spip("inc/Facteur/$sender_class")
			and class_exists($FacteurClass = "SPIP\\Facteur\\{$sender_class}")){

			$facteur = new $FacteurClass($options);
		}
	}
	else {
		// on passe par facteur_factory() pour prendre en compte les options par defaut de Facteur
		include_spip('inc/facteur');
		$facteur = facteur_factory($options);
	}

	if ($facteur) {
		if (!empty($to_send['sujet'])) {
			$facteur->setObjet($to_send['sujet']);
		}
		if (!empty($to_send['email'])) {
			$facteur->setDest($to_send['email']);
		}
		if (!empty($to_send['html']) or !empty($to_send['texte'])) {
			$facteur->setMessage($to_send['html'] ?? '', $to_send['texte'] ?? '');
		}

		// We are Bulk : https://support.google.com/mail/bin/answer.py?hl=en&answer=81126
		$facteur->AddCustomHeader("Precedence: bulk");

		return $facteur;
	}

	return $fail;
}
