<?php

/**
 * Plugin LinkCheck
 * (c) 2013 Benjamin Grapeloux, Guillaume Wauquier, Guillaume Wauquier
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function inc_linkcheck_remplacer_lien_mort_dist($lien, $linkcheck) {
	if (test_espace_prive()) {
		$lien_corrige = "<mark title=\"" . attribut_html(_T('linkcheck:lien_mort')). "\">$lien</mark>";
	}
	else {
		$p = strpos($lien, '>');
		$lien_short = substr($lien, 0, $p+1);
		$lien_corrige = $lien_short;
		$lien_corrige = vider_attribut($lien_corrige, "href");
		$lien_corrige = inserer_attribut($lien_corrige, "title", _T('linkcheck:lien_mort'));
		$lien_corrige = substr_replace($lien_corrige, "<abbr", 0, 2);
		$lien_corrige = str_replace($lien_short, $lien_corrige, $lien);
		$lien_corrige = str_replace("</a>", "</abbr>", $lien_corrige);
	}
	return $lien_corrige;
}
