<?php
/**
 * Plugin Newsletters
 * (c) 2012 Cedric Morin
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * @param ?int $id_newsletter
 * @return string
 */
function newsletter_set_url_base($id_newsletter = null) {
	if (!empty($GLOBALS['meta']['newsletter_adresse_site'])) {
		unset($GLOBALS['meta']['newsletter_adresse_site']);
	}
	if ($id_newsletter and $url = sql_getfetsel('adresse_site', 'spip_newsletters', 'id_newsletter='.intval($id_newsletter))) {
		$GLOBALS['meta']['newsletter_adresse_site'] = rtrim($url, '/') . '/';
	}
	return newsletter_url_base();
}

/**
 * URL de base de la newsletter, qui respecte le protocole http/https du site public
 * independamment du protocole http/https du site prive
 * @return string
 */
function newsletter_url_base(){
	if (!empty($GLOBALS['meta']['newsletter_adresse_site'])) {
		$base = rtrim($GLOBALS['meta']['newsletter_adresse_site'], '/') . '/';
	}
	else {
		$base = url_de_base();
	}
	$base .= (_DIR_RACINE ? _DIR_RESTREINT_ABS : '');

	// respecter le protocole http/https de l'adresse principale du site
	// car le back-office peut etre en https, mais le site public en http
	$protocole = explode("://",$GLOBALS['meta']['adresse_site']);
	$protocole = reset($protocole) . ":";
	$base = $protocole . protocole_implicite($base);
	return $base;
}

function newsletter_url_absolue($url, $base='') {
	if (!$base) {
		$base = newsletter_url_base();
	}
	return url_absolue($url ?: './', $base);

}
/**
 * un filtre pour transformer les URLs relatives en URLs absolues ;
 * ne s'applique qu'aux textes contenant des liens
 *
 * idem le filtre liens_absolus du core mais ne touche pas aux urls commencant par @@ qui sont en fait des variables
 * + retablit en http les liens interne en https si l'url publique est en http
 *
 * @param string $texte
 * @param string $base
 * @return string
 */
function newsletters_liens_absolus($texte, $base='') {
	if (!$base) {
		$base = newsletter_url_base();
	}
	$base_racine = rtrim(url_absolue(_DIR_RACINE ? _DIR_RACINE : './', $base), '/') . '/';
	$protocole_racine = explode('://', $base_racine);
	$protocole_racine = reset($protocole_racine);
	$base_racine_https = 'https:'.protocole_implicite($base_racine);
	if ($base_racine_https===$base_racine){
		$base_racine_https = '';
	}

	$balises = [
		'href' => ['a', 'ling', 'image'],
		'src' => ['img', 'script'],
	];
	foreach ($balises as $att => $tags) {
		$preg = ",(<(".implode('|', $tags).")[[:space:]]+[^<>]*>),imsS";
		if (preg_match_all($preg, $texte, $liens, PREG_SET_ORDER)) {
			foreach ($liens as $lien) {
				$href = extraire_attribut($lien[0],$att);
				if ($href AND strncmp($href,'#',1)!==0 AND strncmp($href,'@',1)!==0){
					if ($base_racine_https AND strncmp($href,$base_racine_https,strlen($base_racine_https))==0){
						$abs = $base_racine . substr($href,strlen($base_racine_https));
					}
					elseif(strncmp($href, "//", 2) === 0) {
						$abs = "$protocole_racine:" . $href;
					}
					elseif(strpos($href, $GLOBALS['meta']['adresse_site']) === 0) {
						$abs = str_replace($GLOBALS['meta']['adresse_site'], $base_racine, $href);
					}
					else {
						$abs = url_absolue($href, $base);
					}
					if ($abs !== $href){
						$lien_abs = inserer_attribut($lien[0], $att, $abs);
						$texte = str_replace($lien[0], $lien_abs, $texte);
					}
				}
			}
		}
	}

	return $texte;
}


/**
 * Lister les patrons disponibles
 * (en enlevant les masques par configuration et en les titrant comme dans la configuration)
 *
 * @param string $selected
 * @param bool $tout_voir
 * @return array
 */
function liste_choix_patrons($selected=null, $tout_voir = false){
	$patrons = array();
	$files = find_all_in_path("newsletters/","\.html$");
	if (!$files) return $patrons;

	include_spip("inc/config");
	$masquer = lire_config("newsletters/masquer_fond");
	if (!$masquer)
		$masquer = array();
	foreach ($files as $k=>$file){
		$fond = basename($k,'.html');
		//  ignorer les variantes .texte.html et .page.html utilisee pour generer les version textes et page en ligne
		if (count($e = explode(".",$fond))<2
			OR !in_array(end($e),array('page','texte'))){

			if ($tout_voir OR !in_array($fond,$masquer) OR $fond==$selected)
				$patrons[$fond] = afficher_titre_patron($fond);

		}
	}
	return $patrons;
}

/**
 * Afficher le titre d'un patron
 * @param string $patron
 * @return string
 */
function afficher_titre_patron($patron){
	include_spip("inc/newsletters");
	$infos = newsletters_fond_extraire_infos($patron);
	if (isset($infos['titre']))
		return "[$patron] ".$infos['titre'];

	return "[$patron]";
}

/**
 * Inliner du contenu base64 pour presenter les versions de newsletter dans une iframe
 * @param string $texte
 * @param string $type
 * @return string
 */
function newsletters_inline_base64src($texte, $type="text/html"){
	return "data:$type;charset=".$GLOBALS['meta']['charset'].";base64,".base64_encode($texte);
}

/**
 * Mises en formes pour la version en ligne de la newsletter :
 * - ajoute des styles specifiques surchargeables dans css/newsletter_inline.css
 *
 * @param string $page
 * @return string
 */
function newsletter_affiche_version_enligne($page, $inline=true){

	// contextualiser !
	$contextualize = charger_fonction("contextualize","newsletter");
	$infos = array(
		'email' => 'mail@example.org',
		'nom' => '',
		'lang' => $GLOBALS['spip_lang'],
		'url_unsubscribe' => _DIR_RACINE?_DIR_RACINE:"./",
		'listes' => array(),
	);
	$page = $contextualize($page, $infos);

	if ($inline) {
		// css-izer
		if ($f = find_in_path("css/newsletter_inline.css")){
			lire_fichier($f,$css);
			$css = '<style type="text/css">'.$css.'</style>';
			$p = stripos($page,"</head>");
			if ($p)
				$page = substr_replace($page,$css,$p,0);
			else
				$page .= $css;
		}
	}
	return $page;
}

/**
 * Encapsuler les img du bon markup pour qu'elles ne depassent pas de la largeur maxi
 * sans pour autant etre deformee en plein ecran
 *
 * max-width:100% sur un img ne suffit pas
 * Il faut les mettre dans un div en width:100% avec un max-width:Npx correspondant a la taille maxi de l'image
 * et appliquer un width:100% sur l'image
 *
 * @param $texte
 * @return mixed
 */
function newsletter_responsive_img($texte){
	$texte = preg_replace_callback(",<img[^>]*>,Uims",'newsletter_responsive_img_wrap',$texte);
	return $texte;
}

/**
 * Callback de la fonction de dessus
 * @param $m
 * @return string
 */
function newsletter_responsive_img_wrap($m){
	$img = $m[0];
	$w = largeur($img);
	// on n'encapsule que les images de plus de 100px
	if ($w<100) return $img;

	$s = extraire_attribut($img,"style");
	if ($s) $s=rtrim($s,";").";";
	return
		"<div class='responsive-img' style='{$s}width:100%;max-width:{$w}px'>"
		.inserer_attribut($img,"style","{$s}width:100%;height:auto;")
		."</div>";
}

/**
 * Un filtre pour fixer une image
 * appele par l'action fixer_newsletter, mais peut etre utilise aussi directement dans la newsletter pour fixer les images
 * manuellement et forcer une url absolue sur un domaine particulier
 *
 * Le filtre genere une url finissant par #fixed pour ne pas y retoucher si il est rappelle
 * (y compris si c'est un domaine qu'il ne connait pas)
 *
 * @param $src
 * @param $id_newsletter
 * @return bool|string
 */
function newsletter_fixer_image($src,$id_newsletter){
	static $urls_base = [];
	static $dir = array();
	if (!isset($dir[$id_newsletter])){
		$dir[$id_newsletter] = sous_repertoire(_DIR_IMG,"nl");
		$dir[$id_newsletter] = sous_repertoire($dir[$id_newsletter],$id_newsletter);
	}
	include_spip("inc/documents"); //deplacer_fichier_upload

	// recuperer l'image par copie directe si possible
	// reconnaitre toutes les urls (connues) du site
	if (empty($urls_base[$id_newsletter])){
		$urls_base[$id_newsletter][] = $GLOBALS['meta']['adresse_site'] . '/';
		$urls_base[$id_newsletter][] = newsletter_url_absolue(_DIR_RACINE);
		$urls_base[$id_newsletter][] = url_absolue(_DIR_RACINE);
		$urls_base[$id_newsletter] = array_unique($urls_base[$id_newsletter]);
		$urls_base[$id_newsletter] = array_map('protocole_implicite', $urls_base[$id_newsletter]);
	}

	foreach ($urls_base[$id_newsletter] as $url_base) {
		if (strncmp(protocole_implicite($src), $url_base, $l=strlen($url_base)) === 0) {
			$src = _DIR_RACINE . substr(protocole_implicite($src),$l);
			break;
		}
	}
	$url = parse_url($src);

	// Ancien hack :
	// on utilise plus cette methode car yahoo mail n'affiche pas les images avec une ancre
	// mais on continue a la reconnaitre au cas ou elle est utilisee dans des squelettes de newsletter
	//
	// mettre un #fixed sur une url d'image pour indiquer qu'elle a deja ete fixee
	// on ne fait plus rien dans ce cas
	if (isset($url['fragment']) and $url['fragment'] == 'fixed') {
		return false;
	}
	$path_parts = pathinfo($url['path']);

	// nouvelle convention : l'image fixee fini par .fixed.(jpg|png|gif...)
	if (strpos($url['path'], ".fixed.".$path_parts['extension']) !== false) {
		return false;
	}

	$dest = $dir[$id_newsletter].md5($src).".fixed.".$path_parts['extension'];

	if (
		empty($url['scheme'])
		and empty($url['host'])
		and file_exists($url['path'])
	) {
		// on copie le fichier
		deplacer_fichier_upload($url['path'], $dest, false);
	} else {
		include_spip("inc/distant");
		recuperer_url($src, ['file' => $dest]);
	}

	if (!file_exists($dest))
		return false;

	return timestamp($dest);
}

/**
 * Afficher la regle de recurrence en clair a partir de l'ics simplifie
 * @param string $ics
 * @param string $sep
 * @return string
 */
function newsletter_afficher_recurrence_regle($ics,$sep=", "){
	include_spip("inc/newsletters");
	list($date_debut,$rule) = newsletter_ics_to_date_rule($ics);
	include_spip("inc/when");
	return when_rule_to_texte($rule,$sep);
}

/**
 * Afficher la date de debut de la recurrence a partir de l'ics simplifie
 * @param string $ics
 * @return string
 */
function newsletter_afficher_recurrence_debut($ics){
	include_spip("inc/newsletters");
	list($date_debut,$rule) = newsletter_ics_to_date_rule($ics);
	return $date_debut;
}


/**
 * Determiner le format optimal pour aplatir une image
 * On essaie de garder png si source en png/gif et resultat pas trop gros
 * pour une meilleure qualite
 * @param string $img_source
 * @param int $png_limit_size
 * @return string
 */
function newsletter_image_extension_aplatir_format($img_source, $png_limit_size=51200) {
	$extension = 'jpg';
	if (strpos($img_source, '<') !== false) {
		$img_source = extraire_attribut($img_source, 'src');
	}
	$img_source = supprimer_timestamp($img_source);
	$parts = explode('.', $img_source);

	// on conserve gif et png en png, si pas trop gros
	if (in_array(end($parts), array('gif', 'png'))) {
		if (file_exists($img_source) && filesize($img_source) < $png_limit_size) {
			return 'png';
		}
	}
	return $extension;
}
