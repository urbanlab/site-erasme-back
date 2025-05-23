<?php
/**
 * Plugin No-SPAM
 * (c) 2008-2019 Cedric Morin Yterium&Nursit
 * Licence GPL
 *
 */


if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Recuperer le HTML a afficher pour faire confirmer une action par l'utilisateur a son insu
 * (antispam qui declenche l'action uniquement si l'utilisateur charge les ressources de la page apres le POST du formulaire)
 *
 * @param string $function
 * @param string $description
 * @param array $arguments
 * @param string $file
 * @param null $time
 * @param string $method
 * @return string
 */
function nospam_confirm_action_html(
	$function,
	$description,
	$arguments = array(),
	$file = '',
	$time = null,
	$method = 'script') {

	include_spip('action/nospam_confirm_action');
	return nospam_confirm_action_prepare($function, $description, $arguments, $file, $time, $method);
}


/**
 * Calculer un hash qui represente l'utilisateur
 * @return string
 */
function nospam_hash_env() {
	static $res ='';
	if ($res) {
		return $res;
	}
	$ip = explode('.', $GLOBALS['ip']);
	array_pop($ip);
	$ip = implode('.', $ip).'.xxx';
	$res = md5($ip. (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''));
	#spip_log("jeton $res pour ".$ip. $_SERVER['HTTP_USER_AGENT'],"jetons");
	return $res;
}


/**
 * Est-ce qu'on suspecte cet utilisateur d'etre un bot ?
 * @return bool
 */
function nospam_may_be_bot() {
	if (defined('_IS_BOT') and _IS_BOT) {
		return true;
	}
	if (!isset($_SERVER['HTTP_USER_AGENT']) or !strlen($_SERVER['HTTP_USER_AGENT'])) {
		return true;
	}

	if (preg_match(','
	. implode ('|', array(
		// mots generiques supplementaires
		'curl',
		'python-requests',
	)) . ',i', $_SERVER['HTTP_USER_AGENT'])) {
		return true;
	}
	return false;
}

/**
 * Calcule une cle de jeton pour un formulaire
 *
 * @param string $form
 *   nom du formulaire
 * @param string $qui
 *   identifiant du visiteur a qui est attribue le jeton
 * @return string
 *   cle calculee
 */
function nospam_creer_jeton($form, $qui = null) {
	$time = date('Y-m-d-H',$_SERVER['REQUEST_TIME']);
	if (is_null($qui)) {
		if (isset($GLOBALS['visiteur_session']['id_auteur']) and intval($GLOBALS['visiteur_session']['id_auteur'])) {
			$qui = ':'.$GLOBALS['visiteur_session']['id_auteur'].':'.$GLOBALS['visiteur_session']['nom'];
		} elseif (!defined('_IS_BOT') or !_IS_BOT) { // pas de jeton pour les bots qui n'ont rien d'interessant a poster
			$qui = nospam_hash_env();
		}
	}
	include_spip('inc/securiser_action');
	// le jeton prend en compte l'heure et l'identite de l'internaute
	return calculer_cle_action("jeton$form$time$qui");
}

/**
 * Verifie une cle de jeton pour un formulaire
 *
 * @param string $jeton
 *   cle recue
 * @param string $form nom du formulaire
 *   nom du formulaire
 * @param string $qui
 *   identifiant du visiteur a qui est attribue le jeton
 * @return bool cle correcte ?
 */
function nospam_verifier_jeton($jeton, $form, $qui = null) {
	$time = $_SERVER['REQUEST_TIME'];
	$time_old = date('Y-m-d-H', $time-3600);
	$time = date('Y-m-d-H', $time);

	if (is_null($qui)) {
		if (isset($GLOBALS['visiteur_session']['id_auteur']) and intval($GLOBALS['visiteur_session']['id_auteur'])) {
			$qui = ':'.$GLOBALS['visiteur_session']['id_auteur'].':'.$GLOBALS['visiteur_session']['nom'];
		} else {
			$qui = nospam_hash_env();
		}
	}

	$ok = (verifier_cle_action("jeton$form$time$qui", $jeton)
			or verifier_cle_action("jeton$form$time_old$qui", $jeton));
	#if (!$ok)
	#	spip_log("Erreur form:$form qui:$qui agent:".$_SERVER['HTTP_USER_AGENT']." ip:".$GLOBALS['ip'],'fauxjeton');
	return $ok;
}


/**
 * Compte le nombre de caracteres d'une chaine,
 * mais en supprimant tous les liens
 * (qu'ils soient ou non ecrits en raccourcis SPIP)
 * ainsi que tous les espaces en trop
 *
 * @param string $texte
 *   texte d'entree
 * @param bool $propre
 *   passer le texte dans propre ou non
 * @return int
 *   compte du texte nettoye
 */
function nospam_compter_caracteres_utiles($texte, $propre = true) {
	include_spip('inc/charsets');
	if ($propre) {
		$texte = propre($texte);
	}
	$u = $GLOBALS['meta']['pcre_u'];
	// regarder si il y a du contenu en dehors des liens !
	$texte = PtoBR($texte);
	$texte = preg_replace(",<a.*</a>,{$u}Uims", '', $texte);
	// \W matche tous les caracteres non ascii apres 0x80
	// et vide donc les chaines constitues de caracteres unicodes uniquement
	// on remplace par un match qui elimine uniquement
	// les non \w  et les non unicodes
	$texte = trim(preg_replace(",[^\w\x80-\xFF]+,ims", ' ', $texte));

	// on utilise spip_strlen pour compter la longueur correcte
	// pour les chaines unicodes
	return spip_strlen($texte);
}


/**
 * Retourne un tableau d'analyse du texte transmis
 * Cette analyse concerne principalement des statistiques sur les liens
 *
 * @param string $texte texte d'entree
 * @return array rapport d'analyse
 */
function nospam_analyser_spams($texte) {
	$infos = array(
		'caracteres_utiles' => 0, // nombre de caracteres sans les liens
		'nombre_liens' => 0, // nombre de liens
		'caracteres_texte_lien_min' => 0, // nombre de caracteres du plus petit titre de lien
		'contenu_cache' => false, // du contenu est caché en CSS ?
	);

	if (!$texte) {
		return $infos;
	}

	// on travaille d'abord sur le texte 'brut' tel que saisi par
	// l'utilisateur pour ne pas avoir les class= et style= que spip ajoute
	// sur les raccourcis.

	// on ne tient pas compte des blocs <code> et <cadre> ni de leurs contenus
	include_spip('inc/texte_mini');
	if (!function_exists('echappe_html')) { // SPIP 2.x
		include_spip('inc/texte');
	}
	$texte_humain = echappe_html($texte);
	// on repère dans ce qui reste la présence de style= ou class= qui peuvent
	// servir à masquer du contenu
	// les spammeurs utilisent le laxisme des navigateurs pour envoyer aussi style =
	// soyons donc mefiant
	// (mais en enlevant le base64 !)
	$texte_humain = str_replace('class="base64"', '', $texte_humain);
	$hidden = ',(<(img|object)|\s(?:style|class)\s*=[^>]+>),UimsS';
	if (preg_match($hidden, $texte_humain)) {
		// suspicion de spam
		$infos['contenu_cache'] = true;
	}

	include_spip('inc/texte');
	$texte = propre($texte);

	// caracteres_utiles
	$infos['caracteres_utiles'] = nospam_compter_caracteres_utiles($texte, false);

	// nombre de liens
	$liens = array_filter(extraire_balises($texte, 'a'), 'nospam_pas_lien_ancre');
	$infos['nombre_liens'] = count($liens);
	$infos['liens'] = $liens;

	// taille du titre de lien minimum
	if (count($liens)) {
		// supprimer_tags() s'applique a tout le tableau,
		// mais attention a verifier dans le temps que ca continue a fonctionner
		# $titres_liens = array_map('supprimer_tags', $liens);
		$titres_liens = supprimer_tags($liens);
		$titres_liens = array_map('strlen', $titres_liens);
		$infos['caracteres_texte_lien_min'] = min($titres_liens);
	}
	return $infos;
}

/**
 * Vérifier si un lien est *n'est pas* une ancre : dans ce cas, ne pas le compte (ici, fonction de filtre de tableau)
 * Cette analyse concerne principalement des statistiques sur les liens
 *
 * @param string $texte lien
 * @return boolean : true ->
 */
function nospam_pas_lien_ancre($texte) {
	return substr(extraire_attribut($texte, 'href'), 0, 1) == '#' ? false : true;
}

/**
 * Compare les domaines des liens fournis avec la presence dans la base
 *
 * @param array $liens
 *   liste des liens html
 * @param int $seuil
 *   seuil de detection de presence : nombre d'enregistrement qui ont deja un lien avec le meme domaine
 * @param string $table
 *   table sql
 * @param array $champs
 *   champs a prendre en compte dans la detection
 * @param null|string $condstatut
 *   condition sur le statut='spam' pour ne regarder que les enregistrement en statut spam
 * @return bool
 */
function nospam_rechercher_presence_liens_spammes($liens, $seuil, $table, $champs, $condstatut = null) {
	include_spip('inc/filtres');

	if (is_null($condstatut)) {
		$condstatut = 'statut='.sql_quote('spam');
	}
	if ($condstatut) {
		$condstatut = "$condstatut AND ";
	}

	// limiter la recherche au mois precedent
	$trouver_table = charger_fonction('trouver_table', 'base');
	if ($desc = $trouver_table($table)
		and isset($desc['date'])) {
		$depuis = date('Y-m-d H:i:s', strtotime('-1 month'));
		$condstatut .= $desc['date'].'>'.sql_quote($depuis).' AND ';
	}

	// Ne pas prendre en compte les liens sur les domaines explicitement autorisés
	// Il ne faut ni http(s):// ni www dedans, juste le NDD (et éventuellement un sous domaine)
	if (defined('NOSPAM_DOMAINES_AMIS') and NOSPAM_DOMAINES_AMIS) {
		$amis = explode(',', NOSPAM_DOMAINES_AMIS);
		$amis = array_filter(array_map('trim', $amis));
	} else {
		$amis = array();
	}

	foreach (array($GLOBALS['meta']['adresse_site'],url_de_base()) as $a) {
		$host = parse_url($a, PHP_URL_HOST);
		if ($host) {
			$host = explode('.', $host);
			$amis[] = implode('.', array_slice($host, -2));
		}
	}

	if (count($amis)) {
		$amis = array_unique($amis);
		$amis = array_map('preg_quote', $amis);
		$amis = '/('.implode('|', $amis).')$/';
		spip_log("domaines whitelist pour les liens spams : $amis", 'nospam');
	} else {
		$amis = '';
	}

	$hosts = array();
	foreach ($liens as $lien) {
		$url = extraire_attribut($lien, 'href');
		if ($parse = parse_url($url)
		  and !empty($parse['host'])
		  and (!$amis or !preg_match($amis, $parse['host']))) {
			$hosts[] = $parse['host'];
		}
	}

	$hosts = array_unique($hosts);
	$hosts = array_filter($hosts);

	// pour chaque host figurant dans un lien, regarder si on a pas deja eu des spams avec ce meme host
	// auquel cas on refuse poliment le message
	foreach ($hosts as $h) {
		$like = ' LIKE '.sql_quote("%$h%");
		$where = $condstatut . '('.implode("$like OR ", $champs)."$like)";
		if (($n=sql_countsel($table, $where))>=$seuil) {
			// loger les 10 premiers messages concernes pour aider le webmestre
			$_id = id_table_objet($table);
			$all = sql_allfetsel($_id, $table, $where, '', '', '0,10');
			if (function_exists('array_column')) {
				$all = array_column($all, $_id);
			} else {
				$all = array_map('reset', $all);
			}
			spip_log("$n liens trouves $like dans table $table (".implode(',', $all).') [champs '.implode(',', $champs).']', 'nospam');
			return $h;
		}
	}
	return false;
}
