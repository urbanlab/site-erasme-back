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
	return 0;
}
