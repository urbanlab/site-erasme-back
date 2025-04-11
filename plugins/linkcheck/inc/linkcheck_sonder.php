<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction qui teste une URL de spip_linkchecks
 * et qui peut retourner un tableau des liens
 *
 * @param array|int $linkcheck
 * @param bool $force
 * @return $set
 */
function linkcheck_tester_un_linkcheck($linkcheck, $force = false) {
	if (is_numeric($linkcheck)) {
		$linkcheck = sql_fetsel('*', 'spip_linkchecks', 'id_linkcheck='.intval($linkcheck));
	}

	//si le champ est inférieur à 6
	$set = [];
	if ($force || empty($linkcheck['essais']) || $linkcheck['essais'] < 6) {
		if ($linkcheck['distant']) {
			$status = linkcheck_tester_url_externe($linkcheck['url']);
		} else {
			$status = linkcheck_tester_url_interne($linkcheck['url']);
		}
		// si c'était un lien mort par essais>seuil,
		// on ne le reactive pas si on le trouve encore malade lors d'un test forcé
		if ($linkcheck['code'] != '418' || $status['etat'] !== 'malade') {
			$set = [
				'etat' => $status['etat'],
				'code' => $status['code'],
				'redirection' => $status['redirection'] ?? '',
				'essais' => ($status['etat'] === 'malade' ? $linkcheck['essais'] + 1 : 0)
			];
		}
	} else {
		//on abandonne les essais et on le signale comme mort
		$set = [
			'etat' => linkcheck_etats_liens(418), // mort
			'code' => '418', // tea pot
			'redirection' => '',
			'essais' => 99,
		];
	}

	// updater le lien et a minima sa date de maj
	$set['maj'] = date('Y-m-d H:i:s');
	spip_log("linkcheck_tester_un_linkcheck: #".$linkcheck['id_linkcheck'] ." Update " . json_encode($set), 'linkcheck' . _LOG_DEBUG);
	sql_updateq('spip_linkchecks', $set, 'id_linkcheck=' . intval($linkcheck['id_linkcheck']));
	return $set;
}


/**
 * Retourne le statut de l'url externe
 *
 * @param string $url
 * 		L'url externe à tester
 * @return array $ret
 */
function linkcheck_tester_url_externe($url) {
	static $dead_hosts = [];
	spip_log("linkcheck_tester_url_externe: $url", 'linkcheck' . _LOG_DEBUG);
	$ret = [];

	if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
		$url = 'http://' . $url;
	}

	$ret['etat'] = linkcheck_etats_liens(400);
	$ret['code'] = 'no-code';

	$parts = parse_url($url);
	if ($parts === false) {
		$ret['code'] = 'malformed';
		return $ret;
	}
	if (empty($parts['host']) || in_array($parts['host'], $dead_hosts)) {
		$ret['code'] = 'DNS';
		return $ret;
	}

	/**
	 * Fixer le timeout d'une page à 30
	 * Faire croire que l'on est un navigateur normal (pas un bot)
	 */
	$timeout = 10;
	$contexte = [
			'http' => [
				'timeout' => $timeout,
				'follow_location' => true,
				'header' => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.16 (KHTML, like Gecko) Chrome/24.0.1304.0 Safari/537.16\r\n" .
							'Accept-Encoding: gzip, deflate',
			],
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
			]
		];
	if (!defined('_INC_DISTANT_USER_AGENT')) {
		define('_INC_DISTANT_USER_AGENT', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.16 (KHTML, like Gecko) Chrome/24.0.1304.0 Safari/537.16');
	}
	if (!defined('_INC_DISTANT_CONNECT_TIMEOUT')) {
		define('_INC_DISTANT_CONNECT_TIMEOUT', $timeout);
	}

	stream_context_set_default(
		$contexte
	);
	$t = time();
	// récuperer un header décodé dans tous les cas
	if ($head_lines = @get_headers($url)) {
		// get_headers suit les redirections et nous donne tous les headers bout à bout
		// on decode on prenant en compte le status du dernier hit uniquement
		// et on garde la location si besoin pour savoir qu'il y a une redirection ET le status final est X
		$header = linkcheck_recuperer_decoder_headers($head_lines, $url);
	} else {
		// gerer les cas particuliers d'erreur qu'on peut repérer
		// si on a timeout, on ne retente pas
		if (time() >= $t + $timeout) {
			$ret['code'] = 524;
			$ret['etat'] = linkcheck_etats_liens($ret['code']);
			return $ret;
		}

		// si on a echoué, regarder le DNS
		$addr = gethostbyname($parts['host'] . '.');
		if (empty($addr) || $addr === $parts['host'] . '.') {
			$ret['code'] = 'DNS';
			return $ret;
		}

		// sinon on retente avec méthode interne
		$header = linkcheck_get_headers($url);
	}

	if ($header) {
		if (!empty($header['status'])) {
			$ret['code'] = $header['status'];
			$ret['etat'] = linkcheck_etats_liens($header['status']);

			// si c'est une redirection, on a le status de la dernière URL obtenue en suivant les redirections
			// et on stocke l'URL finale correspondant dans 'redirection'
			if (!empty($header['location'])) {
				$ret['redirection'] = $header['location'];
				// TODO : adapter le flag distant en fonction de l'URL de redirection
			}
		} else {
			spip_log("linkcheck_tester_url_externe: status introuvable dans le head pour $url :\n" . implode("\n", $header), 'linkcheck' . _LOG_ERREUR);
			$statut = 503;
			$ret['code'] = $statut;
			$ret['etat'] = linkcheck_etats_liens($statut);
			if (!empty($header['location'])) {
				$ret['redirection'] = $header['location'];
				// TODO : adapter le flag distant en fonction de l'URL de redirection
			}
		}
	} else {
		spip_log("linkcheck_tester_url_externe: pas de header pour $url", 'linkcheck' . _LOG_ERREUR);
	}
	return $ret;
}

/**
 *
 * @param string $url
 * @return array|false
 */
function linkcheck_get_headers($url) {
	include_spip('inc/distant');
	$location = false;
	$url_get = $url;
	$headers = false;
	do {
		list($f, $fopen) = init_http('GET', $url_get, false);

		if (!$f) {
			spip_log("ECHEC init_http $url", 'linkcheck.' . _LOG_ERREUR);
			// si on était en train de suivre une redirection, on arrête là
			// mais du coup on indique que le lien final est bien malade
			if (!empty($headers)) {
				$headers['status'] = false;
			}
			return $headers;
		}
		$headers = recuperer_entetes_complets($f, '');
		if (!empty($headers['location'])) {
			$location = suivre_lien($url_get, $headers['location']);
			// eviter la boucle infinie de redirection
			if ($location === $url_get) {
				break;
			}
			$url_get = $location;
		}
	} while (!empty($headers['location']));

	if ($location) {
		$headers['location'] = $location;
	}

	return $headers;
}

/**
 * Decoder les lignes du header de la même façon que recuperer_entetes_complets
 * si il y a une redirection, on va retrouver potentiellement tous les headers des redirections successives
 * il faut donc prendre en compte uniquement le statut final
 *
 * @param $lines
 * @return array|false
 */
function linkcheck_recuperer_decoder_headers($lines, $url_base) {
	$result_init = ['status' => 0, 'headers' => [], 'last_modified' => 0, 'location' => ''];
	$regexp_first_line_status = ',^HTTP/[0-9]+\.[0-9]+ ([0-9]+),';

	$s = array_shift($lines);
	// le premiere ligne doit être celle du status
	if (!preg_match($regexp_first_line_status, $s, $r)) {
		return false;
	}

	$result = $result_init;
	$result['status'] = intval($r[1]);
	while ($s = array_shift($lines)) {
		if (!empty($result['location'])
		  && preg_match($regexp_first_line_status, $s, $r)) {
			$location = $result['location'];
			$result = $result_init;
			$result['status'] = intval($r[1]);
			$result['location'] = $location;
			$url_base = $location; // si on enchaine une nouvelle redirection, l'url_base n'est plus la même
		}

		$result['headers'][] = $s . "\n";
		if (preg_match(',^([^:]*): *(.*)$,i', $s, $r)) {
			[, $d, $v] = $r;
			$d = strtolower(trim($d));
			if ( $d === 'location' && $result['status'] >= 300 && $result['status'] < 400) {
				if ($location = linkcheck_interpreter_location($v, $url_base)) {
					$result['location'] = $location;
				}
			} elseif ($d === 'last-modified') {
				$result['last_modified'] = strtotime($v);
			} elseif ($d === 'content-length' and strlen(trim($v))) {
				$result['content_length'] = intval($v);
			}
		}
	}
	$result['headers'] = implode('', $result['headers']);

	return $result;
}

function linkcheck_interpreter_location($location, $url_base) {
	include_spip('inc/filtres');
	// si url_base est une url locale 'article123' on n'interprete pas location et on l'ignore
	if (strpos($url_base, '://') === false) {
		return $url_base;
	}
	$location = suivre_lien($url_base, $location);

	if (strpos($location, 'http://') === false
		&& strpos($location, 'https://') === false) {
		return false;
	}

	$parts = parse_url($location);
	// est-ce qu'on a l'air d'avoir une URL de ce site ?
	if (strpos($GLOBALS['meta']['adresse_site'], $parts['host']) !== false) {
		$self_parts = parse_url($GLOBALS['meta']['adresse_site']);
		if ($self_parts['host'] === $parts['host']) {
			$redir_chez_nous = explode('://', $location, 2);
			$redir_chez_nous = end($redir_chez_nous);
			$redir_chez_nous = explode($parts['host'] . '/', $redir_chez_nous, 2);
			$redir_chez_nous = end($redir_chez_nous);
			// essayons de decoder l'URL pour voir si on trouve on objet/id_objet de ce site
			include_spip('inc/urls');
			$url_dans_site = urls_decoder_url($redir_chez_nous);
			if (is_array($url_dans_site)) {
				$entite = array_shift($url_dans_site);
				$contexte = array_shift($url_dans_site);
				if (!empty($contexte[id_table_objet($entite)])) {
					$location = $entite . $contexte[id_table_objet($entite)]; // article123
				}
			}
		}
	}

	return $location;
}

/**
 * Retourne le statut de l'objet ciblé par $url
 *
 * @param string $url
 * 		L'url interne à tester
 *
 * @return array $ret
 */
function linkcheck_tester_url_interne($url) {
	include_spip('inc/lien');
	include_spip('base/objets');
	$ret = [];

	if (strpos($url, '#') === 0) {
		$ret['etat'] = linkcheck_etats_liens('publie');
		$ret['code'] = 'publie';
	}

	$rac = typer_raccourci($url);

	if (count($rac) && !empty($rac[0]) && !empty($rac[2])) {
		$type_objet = $rac[0];
		$id_objet = $rac[2];
		$objet = objet_type(table_objet($type_objet));
		if (objet_test_si_publie($objet, $id_objet)) {
			$ret['etat'] = 'ok';
			$ret['code'] = 200;
			return $ret;
		}
		$table_sql = table_objet_sql($type_objet);
		$nom_champ_id = id_table_objet($type_objet);
		$statut_objet = sql_getfetsel('statut', $table_sql, $nom_champ_id . '=' . $id_objet);

		if (!empty($statut_objet)) {
			if ($type_objet != 'auteur') {
				$ret['etat'] = linkcheck_etats_liens($statut_objet);
				$ret['code'] = $statut_objet;
			} else {
				$ret['etat'] = linkcheck_etats_liens('publie');
				$ret['code'] = 'publie';
			}
		} else {
			$ret['etat'] =  linkcheck_etats_liens('poubelle');
			$ret['code'] = 'poubelle';
		}
	} else {
		$ret['etat'] = linkcheck_etats_liens('poubelle');
		$ret['code'] = 'poubelle';
	}

	return $ret;
}
