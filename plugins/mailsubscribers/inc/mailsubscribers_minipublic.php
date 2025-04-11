<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
 * \***************************************************************************/

/**
 * Présentation des pages d'installation et d'erreurs
 *
 * @package SPIP\Core\Minipublic
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/headers');
include_spip('inc/texte'); //inclue inc/lang et inc/filtres


/**
 * Retourne le début d'une page HTML minimale (de type installation ou erreur)
 *
 * Le contenu de CSS minimales (reset.css, clear.css, minipublic.css) est inséré
 * dans une balise script inline (compactée si possible)
 *
 * @param array $options
 *   string $lang : forcer la langue utilisateur
 *   string $page_title : titre éventuel de la page (nom du site par défaut)
 *   bool $all_inline : inliner les CSS pour envoyer toute la page en 1 hit
 *   string $doctype
 *   string $charset
 *   string $onload
 *   array $css_files : ajouter des fichiers css
 *   string $css : ajouter du CSS inline
 *   string $head : contenu à ajouter à la fin <head> (par inclusion de JS ou JS inline...)
 * @return string
 *    Code HTML
 *
 * @uses html_lang_attributes()
 * @uses minifier() si le plugin compresseur est présent
 * @uses url_absolue_css()
 *
 * @uses utiliser_langue_visiteur()
 * @uses http_no_cache()
 * @internal
 */
function mailsubscribers_minipublic_install_debut_html($options = []) {

	if (function_exists('include_fichiers_fonctions')) {
		include_fichiers_fonctions();
	}
	else {
		// SPIPI 3.x
		include_spip('public/parametrer');
	}
	include_spip('inc/filtres_images_mini');
	if (empty($options['lang'])) {
		// on se limite sur une langue de $GLOBALS['meta']['langues_multilingue'] car on est dans le public
		utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue']);
	}
	else {
		changer_langue($options['lang']);
	}
	http_no_cache();

	$page_title = (isset($options['page_title']) ? $options['page_title'] : $GLOBALS['meta']['nom_site']);
	$doctype = (isset($options['doctype']) ? $options['doctype'] : '<!DOCTYPE html>');
	$doctype = trim($doctype) . "\n";
	$charset = (isset($options['charset']) ? $options['charset'] : 'utf-8');
	$all_inline = (isset($options['all_inline']) ? $options['all_inline'] : true);
	$onLoad = (isset($options['onLoad']) ? $options['onLoad'] : '');
	if ($onLoad) {
		$onLoad = ' onload="' . attribut_html($onLoad) . '"';
	}

	# envoyer le charset
	if (!headers_sent()) {
		header('Content-Type: text/html; charset=' . $charset);
	}

	$css = '';

	if (function_exists('couleur_hex_to_hsl')) {
		if (!empty($options['couleur_fond'])) {
			$couleur_fond = $options['couleur_fond'];
		}
		else {
			$couleur_fond = lire_config('couleur_login', '#db1762');
		}
		$h = couleur_hex_to_hsl($couleur_fond, 'h');
		$s = couleur_hex_to_hsl($couleur_fond, 's');
		$l = couleur_hex_to_hsl($couleur_fond, 'l');
	}else {
		// SPIP < 4 : on force les valeurs correspondant à #db1762
		$h = 337;
		$s = '81%';
		$l = '47%';
	}

	$inline = ':root {'
	  . "--minipublic-color-theme--h: $h;"
	  . "--minipublic-color-theme--s: $s;"
	  . "--minipublic-color-theme--l: $l;}";
	$vars = file_get_contents(find_in_path('css/minipublic.vars.css'));
	$inline .= "\n" . trim($vars);
	if (function_exists('minifier')) {
		$inline = minifier($inline, 'css');
	}
	$files = [
		find_in_theme('reset.css'),
		find_in_theme('clear.css'),
		find_in_path('css/minipublic.css'),
	];
	if (!empty($options['css_files'])) {
		foreach ($options['css_files'] as $css_file) {
			$files[] = $css_file;
		}
	}
	if ($all_inline) {
		// inliner les CSS (optimisation de la page minipublic qui passe en un seul hit a la demande)
		foreach ($files as $name) {
			$file = direction_css($name);
			if (function_exists('minifier')) {
				$file = minifier($file);
			} else {
				$file = url_absolue_css($file); // precaution
			}
			$css .= file_get_contents($file);
		}
		$css = "$inline\n$css";
		if (!empty($options['css'])) {
			$css .= "\n" . $options['css'];
		}
		$css = "<style type='text/css'>$css</style>";
	} else {
		$css = "<style type='text/css'>$inline</style>";
		foreach ($files as $name) {
			$file = timestamp(direction_css($name));
			$css .= "<link rel='stylesheet' href='" . attribut_html($file) . "' type='text/css' />\n";
		}
		if (!empty($options['css'])) {
			$css .= "<style type='text/css'>" . $options['css'] . '</style>';
		}
	}

	return $doctype .
		html_lang_attributes() .
		"<head>\n" .
		'<title>' .
		textebrut($page_title) .
		"</title>\n" .
		"<meta name=\"viewport\" content=\"width=device-width\" />\n" .
		$css .
		(empty($options['head']) ? '' : $options['head']) .
		"</head>\n" .
		"<body{$onLoad} class=\"minipublic\">\n" .
		"\t<div class=\"minipublic-bloc\">\n";
}

/**
 * Retourne la fin d'une page HTML minimale (de type installation ou erreur)
 *
 * @return string Code HTML
 * @internal
 */
function mailsubscribers_minipublic_install_fin_html() {
	return "\n\t</div>\n</body>\n</html>";
}


/**
 * Retourne une page HTML contenant, dans une présentation minimale,
 * le contenu transmis dans `$corps`.
 *
 * Appelée pour afficher un message ou une demande de confirmation simple et rapide
 *
 * @param string $corps
 *   Corps de la page
 * @param array $options
 *   @return string
 *   HTML de la page
 * @see  mailsubscribers_minipublic_install_debut_html()
 *   string $titre : Titre à l'affichage (différent de $page_title)
 *   int $status : status de la page
 *   string $footer : pied de la box en remplacement du bouton retour par défaut
 * @uses mailsubscribers_minipublic_install_debut_html()
 * @uses mailsubscribers_minipublic_install_fin_html()
 *
 */
function mailsubscribers_minipublic($corps, $options = []) {

	if (class_exists('Spip\Afficher\Minipublic')) {
		$minipublic = new Spip\Afficher\Minipublic();
		return $minipublic->page($corps, $options);
	}

	// par securite
	if (!defined('_AJAX')) {
		define('_AJAX', false);
	}


	$titre = (isset($options['titre']) ? $options['titre'] : '');
	$status = (isset($options['status']) ? intval($options['status']) : 200);
	$status = ($status ?: 200);

	$url_site = url_de_base();
	$header = "<header>\n" .
	  '<h1><a href="' . attribut_html($url_site) . '">' . interdire_scripts($GLOBALS['meta']['nom_site']) . "</a></h1>\n";

	if ($titre) {
		$header .= '<h2>' . interdire_scripts($titre) . '</h2>';
	}
	$header .= '</header>';

	$corps = "<div class='corps'>\n" .
		$corps .
		'</div>';


	if (isset($options['footer'])) {
		$footer = $options['footer'];
	}
	else {
		$footer = '<a href="' . attribut_html($url_site) . '">' . _T('retour') . "</a>\n";
	}
	if (!empty($footer)) {
		$footer = "<footer>\n{$footer}</footer>";
	}

	http_response_code($status);

	$html = mailsubscribers_minipublic_install_debut_html($options)
		. $header
		. $corps
		. $footer
		. mailsubscribers_minipublic_install_fin_html();

	if (
		$GLOBALS['profondeur_url'] >= (_DIR_RESTREINT ? 1 : 2)
		and empty($options['all_inline'])
	) {
		define('_SET_HTML_BASE', true);
		include_spip('public/assembler');
		$GLOBALS['html'] = true;
		page_base_href($html);
	}
	return $html;
}

/**
 * Fonction helper pour les erreurs
 * @param ?string $message_erreur
 * @param array $options
 * @return string
 *@see mailsubscribers_minipublic()
 *
 */
function mailsubscribers_minipublic_erreur($message_erreur = null, $options = []) {

	if (class_exists('Spip\Afficher\Minipublic')) {
		$minipublic = new Spip\Afficher\Minipublic();
		return $minipublic->pageErreur($message_erreur, $options);
	}

	if (empty($message_erreur)) {
		if (empty($options['lang'])) {
			utiliser_langue_visiteur();
		}
		else {
			changer_langue($options['lang']);
		}
		$message_erreur = _T('info_acces_interdit');
	}
	$corps = "<div class='msg-alert error'>"
		. $message_erreur
		. '</div>';
	if (empty($options['status'])) {
		$options['status'] = 403;
	}
	return mailsubscribers_minipublic($corps, $options);
}
