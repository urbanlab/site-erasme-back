<?php
/*
 * Plugin Facteur 2
 * (c) 2009-2011 Collectif SPIP
 * Distribue sous licence GPL
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_configurer_mailshot_charger_dist(){
	include_spip('inc/cvt_configurer');
	include_spip('inc/mailshot');

	$valeurs = cvtconf_formulaires_configurer_recense('configurer_mailshot');
	$valeurs['editable'] = true;

	$valeurs['_smtp_password'] = isset($valeurs['smtp']['password']) ? $valeurs['smtp']['password'] : '';
	$valeurs['smtp']['password'] = '';

	foreach([
		        'mailjet_secret_key',
		        'sparkpost_api_key',
		        'mandrill_api_key',
		        'sendinblue_api_key',
	        ] as $_key){
		$valeurs['_'.$_key] = $valeurs[$_key];
		$valeurs[$_key] = '';
	}

	include_spip('inc/facteur');
	$facteur = facteur_factory();
	$valeurs['_from_defaut'] = $facteur->From;
	if ($facteur->FromName){
		$valeurs['_from_defaut'] = $facteur->FromName . ' &lt;'.$valeurs['_from_defaut'].'&gt;';
	}

	$valeurs['_fail_ratio'] = mailshot_fail_ratio_alert_level();
	$valeurs['_default_facteur_allowed'] = (mailshot_is_facteur_default_config_allowed() ? ' ' : '');
	return $valeurs;
}

function formulaires_configurer_mailshot_verifier_dist(){

	$erreurs = array();
	return $erreurs;
}

function formulaires_configurer_mailshot_traiter_dist(){
	include_spip('inc/config');
	// reinjecter les password pas saisis si besoin
	$restore_after_save = array();
	if ($smtp = _request('smtp') AND !$smtp['password']){
		$restore_after_save['smtp'] = $smtp;
		$smtp['password'] = lire_config('mailshot/smtp/password');
		set_request('smtp',$smtp);
	}
	foreach([
		        'mailjet_secret_key',
		        'sparkpost_api_key',
		        'mandrill_api_key',
		        'sendinblue_api_key',
	        ] as $_key){
		if (!_request($_key)){
			$restore_after_save[$_key] = '';
			set_request($_key,lire_config('mailshot/'.$_key));
		}
	}

	include_spip('inc/cvt_configurer');
	$trace = cvtconf_formulaires_configurer_enregistre('configurer_mailshot', array());
	$res = array('message_ok' => _T('config_info_enregistree') . $trace, 'editable' => true);

	foreach($restore_after_save as $k=>$v){
		set_request($k,$v);
	}

	$config = lire_config("mailshot/");
	if ($mailer = $config['mailer']
	  AND include_spip("bulkmailer/$mailer")
	  AND $config = charger_fonction($mailer."_config","bulkmailer",true)){
		$config($res);
	}

	return $res;
}
