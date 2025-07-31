<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pipeline qui ajoute des taches automatiques
 *
 * @param array $taches
 */
function era_taches_generales_cron($taches) {
	$taches['generer_slug'] = 300; // toutes les 5 minutes on regarde si il y a des sites à tester
	return $taches;
}

function era_post_edition($flux) {
	$tables_identifiables = identifiants_lister_tables_identifiables(true);
	foreach ($tables_identifiables as $table) {
		if (isset($flux['args']['table']) && $flux['args']['table'] === $table
			&& isset($flux['args']['action']) && $flux['args']['action'] === 'modifier'
			&& isset($flux['args']['id_objet'])
		) {
			$champ = sql_getfetsel(
				'titre',
				$flux['args']['table'],
				table_objet($flux['args']['table']) . '=' . intval($flux['args']['id_objet'])
			);
			if ($table == 'spip_auteurs') {
				$champ = sql_getfetsel(
					'nom',
					$flux['args']['table'],
					table_objet($flux['args']['table']) . '=' . intval($flux['args']['id_objet'])
				);
			}
			sql_updateq(
				$flux['args']['table'],
				['identifiant' => identifiant_slug($champ)],
				table_objet($flux['args']['table']) . '=' . intval($flux['args']['id_objet'])
			);
		}
	}
}
