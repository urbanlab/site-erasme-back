<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/filtres');
function genie_generer_slug($time) {
	$articles = sql_allfetsel('*', 'spip_articles', ['identifiant = ""'],'','','0,100');
	foreach ($articles as $article) {
		sql_updateq('spip_articles', ['identifiant' => identifiant_slug($article['titre'])], 'id_article=' . intval($article['id_article']));
	}
	$rubriques = sql_allfetsel('*', 'spip_rubriques', ['identifiant = ""'],'','','0,100');
	foreach ($rubriques as $rubrique) {
		sql_updateq('spip_rubriques', ['identifiant' => identifiant_slug($rubrique['titre'])], 'id_rubrique=' . intval($rubrique['id_rubrique']));
	}
	$mots = sql_allfetsel('*', 'spip_mots', ['identifiant = ""'],'','','0,100');
	foreach ($mots as $mot) {
		sql_updateq('spip_mots', ['identifiant' => identifiant_slug($mot['titre'])], 'id_mot=' . intval($mot['id_mot']));
	}
	$auteurs = sql_allfetsel('*', 'spip_auteurs', ['identifiant = ""'],'','','0,100');
	foreach ($auteurs as $auteur) {
		sql_updateq('spip_auteurs', ['identifiant' => identifiant_slug($auteur['nom'])], 'id_auteur=' . intval($auteur['id_auteur']));
	}
	return 0;
}
