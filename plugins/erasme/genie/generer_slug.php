<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/filtres');
function genie_generer_slug($time) {
	$tables_identifiables = identifiants_lister_tables_identifiables(true);
	foreach ($tables_identifiables as $table) {
		$objets = sql_allfetsel('*', $table, ['identifiant = ""'], '', '', '0,100');
		foreach ($objets as $objet) {
			$champ = $objet['titre'];
			if ($table == 'spip_auteurs') {
				$champ = $objet['nom'];
			}
			sql_updateq(
				$table,
				['identifiant' => identifiant_slug($champ)],
				id_table_objet($objet) . '=' . intval(id_table_objet($objet))
			);
		}
	}
	return 0;
}
