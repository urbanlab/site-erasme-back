<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

# Constante surchargeable à placer dans config/mes_options.php en cas de multidomaines
# Liste de domaines supplémentaires considérés comme locaux :
# define('CORRECTION_LIENS_INTERNES_AUTRES_DOMAINES', 'http://domaine2.tld/, http://domaine3.tld');

/**
 * Corrige les liens internes juste avant l'édition d'un texte
 * @param array $flux l'entrée du pipeline
 * @return array $flux le flux modifié
**/
function correction_liens_internes_pre_edition($flux) {
	// Les forums des tickets : ne jamais corriger
	$id_forum = $flux['args']['id_objet'];
	if (
		(_request('exec') === 'ticket' || _request('page') === 'ticket')
		|| (
			$flux['args']['action'] === 'modifier'
			&& $flux['args']['type'] === 'forum'
			&& $id_forum
			&& sql_getfetsel('objet', 'spip_forum', "id_forum=$id_forum") === 'ticket'
		)
	) {
		return $flux;
	}
	if ($flux['args']['action'] == 'modifier') {
		$sans_raccourci = pipeline('correction_liens_internes_sans_raccourci', [
				'article' => ['virtuel', 'url_site'],
				'site' => ['url_site'],
			]);
		$ignorer =  pipeline(
			'correction_liens_internes_ignorer',
			[
				'newsletter' => ['html_email', 'texte_email', 'html_page',
				'ticket' => '*'
				]
			]
		);
		$type = $flux['args']['type'];

		foreach ($flux['data'] as $champ => $valeur) {
			$raccourci_spip = true;
			if (
				isset($ignorer[$type])
				&& (
					in_array($champ, $ignorer[$type])
					|| $ignorer[$type] === '*'
				)
			) {
				continue;
			}
			if (isset($sans_raccourci[$type]) && in_array($champ, $sans_raccourci[$type])) {
				$raccourci_spip = false;
			}
			$flux['data'][$champ] = correction_liens_internes_correction($valeur, $raccourci_spip);
		}
	}
	return $flux;
}

/**
 * A partir d'une url privée, donne les infos sur l'objet auquel on renvoie
 * @param string $mauvaise_url l'url privée
 * @param array $composants_url les composants de l'url, parsée par php
 * @return array ($objet, $id_objet, $ancre)
 **/
function correction_liens_internes_correction_url_prive($mauvaise_url, $composants_url) {
	// Pour le cas où on a copié-collé une URL depuis espace public.
	if (array_key_exists('fragment', $composants_url)) {
		$ancre = $composants_url['fragment'];
	} else {
		$ancre = '';
	}
	$args = [];
	parse_str($composants_url['query'], $args);
	$exec = str_replace('_edit', '', $args['exec']); #prendre en compte les _edit
	if (array_key_exists('id_' . $exec, $args)) {
		$objet = $exec;
		$id_objet = $args['id_' . $objet];
	} else {
		$objet = '';
		$id_objet = '';
	}
	return [$objet, $id_objet, $ancre];
}

/**
 * A partir d'une url publique, donne les infos sur l'objet auquel on renvoie
 * @param string $mauvaise_url l'url publique
 * @param array $composants_url les composants de l'url, parsée par php
 * @return array ($objet, $id_objet, $ancre)
 **/
function correction_liens_internes_correction_url_public($mauvaise_url, $composants_url) {
	$ancre = isset($composants_url['fragment']) ? '#' . $composants_url['fragment'] : '';

	// Url courte /<xx> (avec ou sans le plugin url par numero d'article)
	if (preg_match('#^\/(\d*)$#', $composants_url['path'], $match)) {
		return ['article', $match[1], $ancre];
	}

	list($fond, $contexte) = urls_decoder_url(str_replace($ancre, '', $mauvaise_url));
	if (
			($objet = isset($contexte['type']) ? $contexte['type'] : $fond) &&
			($id_objet = $contexte[id_table_objet($objet)])
	) {
	}
	else {
		// on tente de reconnaitre les formats simples...
		if (isset($composants_url['query'])) {
			parse_str($composants_url['query'], $composants_url);
		}
		if (($objet = $composants_url[_SPIP_PAGE] ?? '') && ($id_objet = $composants_url[id_table_objet($objet)] ?? '')) {
			;//Il se passe rien, c'est fait exprès, vu qu'on a trouvé objet et id_objet
		} else {
			$mauvaise_url = str_replace(url_de_base(), '', $mauvaise_url);
			$nettoyage = nettoyer_url_page($mauvaise_url, $composants_url);
			$composants_url = $nettoyage[0] ?? '';
			$objet = $nettoyage[1] ?? '';
			if ($objet) {
				$id_objet = $composants_url[id_table_objet($objet)];
			} else {
				$id_objet =  '';
			}
		}
	}
	return [$objet, $id_objet, $ancre];
}

/**
 * Parse un texte, à la recherche des liens erronnées, et les corriges
 * @param string|array $texte cela peut être tableau si jamais la fonction est appelé à reception d'un champs extra type checkbox. Mais sinon la plupart du temps c'est string
 * @param bool $raccourci_spip=true si on doit tester la présence du raccourci [->], false si on remplace directement les urls même hors raccourcis SPIP
 * @return string le texte modifié
 **/
function correction_liens_internes_correction($texte, $raccourci_spip = true) {
	if (!$texte) {
		return $texte;
	}

	// C'est un tableeau sérialisé en PHP ? on le sérialize en json pour éviter les ennuis
	$serialize_php = false;
	if (is_string($texte) && $tmp = @unserialize($texte)) {
		$texte = json_encode($tmp, JSON_UNESCAPED_SLASHES);
		$serialize_php =  true;
	}

	// C'est un tableau (typiquement pour les champs extras checkbox) ? on serialise en json
	$is_array = false;
	if (is_array($texte)) {
		$texte = json_encode($texte, JSON_UNESCAPED_SLASHES);
		$is_array = true;
	}

	$texte = correction_liens_internes_echapper_balises($texte);

	// traiter d'autre domaines ?
	if ($domaines = correction_liens_internes_autres_domaines()) {
		$domaines_origine = $domaines;
		$domaines = array_unique(array_merge([url_de_base()], $domaines));
		array_walk($domaines, function ($v) {
			return preg_quote($v, '#');
		});
		$url_site = '(?:' . join('|', $domaines) . ')';
	} else {
		$domaines_origine = [url_de_base()];
		$url_site = preg_quote(url_de_base());
	}
	// http ou https, même combat
	$url_site = str_replace('https\://', 'https?\://', $url_site);
	$url_site = str_replace('http\://', 'https?\://', $url_site);

	// chercher aussi les formes échappées des urls (cas de formidable avec `formidable_serialize()`
	$url_site = str_replace('/', '\\\\?/', $url_site);

	// on repère les mauvaises URLs
	$match = [];
	preg_match_all("#(\[.*\s*->\s?($url_site.*)\]|({$url_site}[^\s]*?))#U", $texte, $match, PREG_SET_ORDER);

	include_spip('inc/urls');

	foreach ($match as $lien) {
		$mauvais_raccourci = $lien[0];
		$mauvaise_url = array_pop($lien);
		$mauvaise_url = trim($mauvaise_url);
		$mauvaise_url = trim($mauvaise_url, '.');
		$bonne_url = correction_liens_internes_trouver_bonne_url($mauvaise_url, $domaines_origine);
		if ($bonne_url) {
			if ($raccourci_spip && strpos($mauvais_raccourci, '->') === false) {
					$bon_raccourci = "[->$bonne_url]";
			} else {
				$bon_raccourci = str_replace($mauvaise_url, $bonne_url, $mauvais_raccourci);
			}
			$texte = str_replace($mauvais_raccourci, $bon_raccourci, $texte);
			spip_log(self() . (_request('self') ? ' / ' . _request('self') : '')  //pour crayons notamment...
			. " : $mauvais_raccourci => $bon_raccourci", 'liens_internes.' . _LOG_AVERTISSEMENT);
		}
	}


	$texte = correction_liens_internes_retrouver_balises($texte);

	if ($serialize_php) {
		$texte = serialize(json_decode($texte, true));
	}
	if ($is_array) {
		$texte = json_decode($texte, true);
	}
	return $texte;
}

/**
 * Trouver la bonne url a partir de la mauvaise
 * @param string $mauvaise_url la mauvaise url
 * @param array $domaines_origine les domaines
 * @return string la bonne url
**/
function correction_liens_internes_trouver_bonne_url($mauvaise_url, $domaines_origine) {

	// http:\/\/ -> http:// (si jamais on est passé par un json_encode avant, comme dans le cas de formidable)
	$mauvaise_url = str_replace('\\/', '/', $mauvaise_url);

	// alias historiques
	static $racc = ['article' => '', 'auteur' => 'aut', 'rubrique' => 'rub', 'breve' => 'br'];
	$bonne_url = '';
	$mauvaise_url_reelle = str_replace($domaines_origine, url_de_base(), $mauvaise_url);
	$composants_url = parse_url($mauvaise_url_reelle);

	// Normaliser https/http en suivant la norme de url_de_base
	$url_de_base = url_de_base();
	if ($composants_url['scheme'] === 'http' && strpos($url_de_base, 'https://') === 0) {
		$mauvaise_url = str_replace('http://', 'https://', $mauvaise_url);
		$mauvaise_url_reelle = str_replace('http://', 'https://', $mauvaise_url_reelle);
		$composants_url['scheme'] = 'https';
	} elseif ($composants_url['scheme'] === 'https' && strpos($url_de_base, 'http://') === 0) {
		$mauvaise_url = str_replace('https://', 'http://', $mauvaise_url);
		$mauvaise_url_reelle = str_replace('https://', 'http://', $mauvaise_url_reelle);
		$composants_url['scheme'] = 'http';
	}

	// Url copiée depuis le privé ou depuis le public?
	if (strrpos($composants_url['path'], _DIR_RESTREINT_ABS) != false) {
		list ($objet, $id_objet,$ancre) = correction_liens_internes_correction_url_prive($mauvaise_url, $composants_url);
	}
	else {
		list ($objet, $id_objet,$ancre) = correction_liens_internes_correction_url_public($mauvaise_url_reelle, $composants_url);
	}
	if (!$objet && !$id_objet && strpos($mauvaise_url_reelle, str_replace(_DIR_RACINE, '', _DIR_IMG)) != false) {
		$url_doc = str_replace([url_de_base(), str_replace(_DIR_RACINE, '', _DIR_IMG)], '', $mauvaise_url_reelle);
		if (defined('_CORRECTION_LIENS_INTERNES_DOCUMENTS_SANS_QUERY')) {
			$url_doc = parse_url($url_doc, PHP_URL_PATH);
		}
		$id_objet = sql_getfetsel('id_document', 'spip_documents', 'fichier=' . sql_quote($url_doc));
		$objet = 'document';
	}
	if ($objet && $id_objet) {
		if (isset($racc[$objet])) {
			$objet = $racc[$objet];
		}
		// Exception historique : sites, cf https://core.spip.net/issues/4283
		if ($objet === 'site') {
			if (!defined('_CORRECTION_LIENS_INTERNES_LIEN_SITES')) {
				define('_CORRECTION_LIENS_INTERNES_LIEN_SITES', 'site');
			}
			$objet = _CORRECTION_LIENS_INTERNES_LIEN_SITES;
		}
		$bonne_url  = $objet . $id_objet . $ancre;
	}
	return $bonne_url;
}
/**
 * Retourne les domaines qu'on peut gérer, en plus du domaine de la requette http courante
 * @return array
**/
function correction_liens_internes_autres_domaines() {
	// si la constante est définie, prendre en compte les domaines déclarés
	$autres_domaines = defined('CORRECTION_LIENS_INTERNES_AUTRES_DOMAINES')
	? preg_split('#([\s,|])+#i', CORRECTION_LIENS_INTERNES_AUTRES_DOMAINES) : [];

	// si le plugin multidomaine est actif, prendre en compte tous les domaines déclarés
	if (test_plugin_actif('multidomaines')) {
		$config_multi = lire_config('multidomaines');
		foreach ($config_multi as $key => $value) {
			if (preg_match('#editer_url#', $key) && $value) {
				$autres_domaines[] = $value;
			}
		}
	}
	// mettre en forme les domaines
	foreach ($autres_domaines as $i => $v) {
		// ajouter un slash final si nécessaire
		if (substr($v, -1) != '/') {
			$autres_domaines[$i] = $v . '/';
		}
		// ajouter http:// par défaut si pas de scheme
		$infos = parse_url($v);
		if (!$infos['scheme']) {
			$autres_domaines[$i] = 'http://'  . $v;
		}
	}
	return $autres_domaines;
}

/**
 * Prend un texte, et échappe tout ce qui se trouve en balise
 * évite ainsi de transformer <a href="http://monsite.test/article1"> en <a href="[->1]">
 * on considère qu'une personne qui utilise directement du code html est consciente.
 * @param string $texte
 * @return string $texte
**/
function correction_liens_internes_echapper_balises(string $texte): string {
	preg_match_all('/<(.*)>/mU', $texte, $matches, PREG_SET_ORDER);
	foreach ($matches as $m) {
		$texte = str_replace($m[0], '<' . base64_encode($m[1]) . '>', $texte);
	}
	return $texte;
}


/**
 * Retrouve les balises que l'on a échappé au préalable, et les remets
 * @param string $texte
 * @return string $texte
**/
function correction_liens_internes_retrouver_balises(string $texte): string {
	preg_match_all('/<(.*)>/mU', $texte, $matches, PREG_SET_ORDER);
	foreach ($matches as $m) {
		$texte = str_replace($m[0], '<' . base64_decode($m[1]) . '>', $texte);
	}
	return $texte;
}
