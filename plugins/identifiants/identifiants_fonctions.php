<?php
/**
 * fonctions utiles au plugin Identifiants
 *
 * @plugin     Identifiants
 * @copyright  2016
 * @author     Tcharlss
 * @licence    GNU/GPL
 * @package    SPIP\Identifiants\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Retourne une liste des tables pour lesquelles on peut gérer les identifiants.
 *
 * Ce sont par défaut les tables sélectionnées dans la config.
 *
 * On peut optionnellement inclure les tables ayant nativement une colonne `identifiant`,
 * qui ne sont pas incluses dans la config.
 *
 * @note
 * On la met ici plutôt que dans inc/identifiants.php,
 * car elle est utilisée dans declarer_tables_objets_sql,
 * et donc ça évite de tout charger en permanence.
 *
 * @return array
 *     Liste de tables
 */
function identifiants_lister_tables_identifiables(bool $inclure_tables_natives = false) : array {

	static $memory;
	$hash = (int) $inclure_tables_natives;
	if (isset($memory[$hash])) {
		return $memory[$hash];
	}

	include_spip('inc/config');
	$tables_selectionnees = array_filter(lire_config('identifiants/objets') ?: []);
	$tables_natives       = identifiants_lister_tables_natives();
	if (!$inclure_tables_natives) {
		$tables = array_diff($tables_selectionnees, $tables_natives);
	} else {
		$tables = array_unique(array_merge($tables_selectionnees, $tables_natives));
	}

	$memory[$hash] = $tables;

	return $tables;
}


/**
 * Renvoie la liste des tables disposant nativement d'une colonne `identifiant`
 *
 * @note
 * On la met ici plutôt que dans inc/identifiants.php,
 * car elle est utilisée dans declarer_tables_objets_sql,
 * et donc ça évite de tout charger en permanence.
 *
 * @return Array
 */
function identifiants_lister_tables_natives() {
	include_spip('inc/config');
	$tables_repertoriees = lire_config('identifiants/tables_repertoriees', []);
	$tables_natives = array_keys(array_filter($tables_repertoriees));

	return $tables_natives;
}

/**
 * Retourne les identifiants utiles pour un objet éditorial
 *
 * @return array<int, string>
 */
function identifiants_utiles(string $objet, ?string $lang = null, bool $unused_only = true) {
	include_spip('inc/identifiants');
	$type = objet_type($objet);
	$ids = identifiants_lister_utiles($lang, $unused_only);
	return $ids[$type] ?? [];
}


/**
 * Balise IDENTIFIANT
 *
 * Retourne l'identifiant de l'objet du contexte
 * ou l'identifiant de l'objet,id_objet passé en paramètre
 *
 * @example `#IDENTIFIANT`
 * @example `#IDENTIFIANT{article, 3}`
 */
function balise_IDENTIFIANT_dist($p) {
	$_objet = interprete_argument_balise(1, $p);
	$_id = interprete_argument_balise(2, $p);

	if (!$_objet) {
		$p->code = champ_sql('identifiant', $p, null) ?? "''";
	} elseif ($_objet and $_id) {
		if (_SPIP_VERSION_ID >= 41000) {
			$p->code =  'generer_objet_info((int) ' . $_id . ',' . $_objet . ', "identifiant")';
		} else {
			$p->code =  'generer_info_entite(' . $_id . ',' . $_objet . ', "identifiant")';
		}
	} else {
		$msg = ['zbug_balise_sans_argument', ['balise' => zbug_presenter_champ($p)]];
		erreur_squelette($msg, $p);
		$p->interdire_scripts = true;
		return $p;
	}

	$p->interdire_scripts = false;

	return $p;
}


/**
 * Balise IDENTIFIANTS_LISTER_IDS
 *
 * Retourne les id_objet de l'objet éditorial demandé, pour un identifiant donné
 *
 * @example `#IDENTIFIANTS_LISTER_IDS{rubrique, actualites}`
 */
function balise_IDENTIFIANTS_LISTER_IDS_dist($p) {
	$_objet = interprete_argument_balise(1, $p);
	$_identifiant = interprete_argument_balise(2, $p);

	if ($_objet and $_identifiant) {
		$p->code =  'identifiants_lister_ids(' . $_objet . ', ' . $_identifiant . ')';
	} else {
		$msg = ['zbug_balise_sans_argument', ['balise' => zbug_presenter_champ($p)]];
		erreur_squelette($msg, $p);
		$p->interdire_scripts = true;
		return $p;
	}

	$p->interdire_scripts = false;

	return $p;
}

/**
 * Pour un type d’objet et un identifiant donné, retourne la liste des id_objet concernés.
 *
 * @return array<int>
 */
function identifiants_lister_ids(string $type, string $identifiant): array {
	static $ids = [];

	if (!isset($ids[$type][$identifiant])) {
		$_id = id_table_objet($type);
		$liste = sql_allfetsel($_id, table_objet_sql($type), [
			'identifiant = ' . sql_quote($identifiant),
		]);
		$liste = array_column($liste, $_id);
		$ids[$type][$identifiant] = $liste;
	}

	return $ids[$type][$identifiant];
}



// ====================
// FONCTIONS DEPRECIÉES
// ====================


/**
 * Retourne l'identifiant d'un objet
 *
 * @deprecated version 2.0.0
 * @uses generer_info_entite()
 * @param string $objet
 *     Le type de l'objet
 * @param int $id_objet
 *     L'identifiant numérique de l'objet
 * @return String|Null
 */
function identifiant_objet($objet, $id_objet) {
	include_spip('inc/filtres');
	return generer_info_entite($id_objet, $objet, 'identifiant');
}
