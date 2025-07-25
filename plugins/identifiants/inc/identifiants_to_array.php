<?php
/**
 * Itérateur « identifiants » du plugin Identifiants
 *
 * @plugin     Identifiants
 * @copyright  2016
 * @author     tcharlss
 * @licence    GNU/GPL
 * @package    Identifiants/Iterateur/Identifiants
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Renvoie une liste d'identifiants et quelques infos sur l'objet associé : type, id, titre et langue
 *
 * @param String|null $table_objet
 *     Nom d'une table ou d'un objet pour restreindre la sélection
 * @return array
 *     Tableau associatif issu de la requête SQL
 */
function inc_identifiants_to_array_dist(? string $table_objet = null): array {

	include_spip('base/abstract_sql');
	include_spip('base/objets');
	include_spip('inc/utils');

	$lignes  = [];
	$tables_identifiables = identifiants_lister_tables_identifiables(true);

	// Si on limite à une table, vérifier qu'elle fait partie des identifiables
	if (
		$table_objet
		and $table_objet = table_objet_sql($table_objet)
	) {
		$tables_identifiables = array_intersect([$table_objet], $tables_identifiables);
	}

	foreach ($tables_identifiables as $table) {
		$objet       = objet_type($table);
		$cle_objet   = id_table_objet($objet);
		$titre       = objet_info($objet, 'titre');
		$field       = objet_info($objet, 'field') ?? [];
		$champ_lang  = (array_key_exists('lang', $field) ? 'lang' : '');
		$select      = [
			'identifiant',                             // #IDENTIFIANT
			sql_quote($objet).' AS objet',             // #OBJET → article
			$cle_objet.' AS id_objet',                 // #ID_OBJET → xx
			$titre,                                    // #TITRE → xx
			$champ_lang ?: sql_quote('') . ' AS lang', // #LANG → xx
		];
		$from    = $table;
		$where   = ['identifiant != ' . sql_quote('')];
		$groupby = $cle_objet;
		if ($lignes_table = sql_allfetsel($select, $from, $where, $groupby)) {
			$lignes = [...$lignes, ...$lignes_table];
		}
	}

	return $lignes;
}
