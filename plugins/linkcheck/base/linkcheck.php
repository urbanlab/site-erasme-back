<?php

/**
 * Plugin LinkCheck
 * (c) 2013 Benjamin Grapeloux, Guillaume Wauquier
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclaration des alias de tables et filtres automatiques de champs
 */
function linkcheck_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['linkchecks'] = 'linkchecks';

	return $interfaces;
}

function linkcheck_declarer_tables_objets_sql($tables) {
	$tables['spip_linkchecks'] = [
		'type' => 'linkcheck',
		'principale' => 'oui',
		'type_surnoms' => [],
		'page' => '',
		'date' => 'date',
		'field' => [
			'id_linkcheck'		=> 'bigint(21) NOT NULL',
			'url'				=> "text NOT NULL DEFAULT ''",
			'distant'			=> 'boolean',
			'etat'				=> "varchar(10) NOT NULL DEFAULT ''",
			'code'				=> "varchar(10) NOT NULL DEFAULT ''",
			'redirection'		=> "text NOT NULL DEFAULT ''",
			'essais'			=> 'int(1) DEFAULT 0',
			'date'				=> "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'publie'			=> "varchar(3) NOT NULL DEFAULT ''",
			'maj'				=> 'TIMESTAMP'
		],
		'key' => [
			'PRIMARY KEY'	=> 'id_linkcheck',
		],
		'join' => [
			'id_linkcheck' => 'id_linkcheck',
		],
		'tables_jointures' => ['']
	];
	$tables[]['tables_jointures'][] = 'linkchecks_liens';

	// desactiver le check par défaut sur ces tables
	// reconfigurable par plugin perso qui utilise linkcheck
	foreach (['spip_syndic_articles', 'spip_paquets', 'spip_plugins', 'spip_linkchecks', 'spip_tickets'] as $t) {
		if (!empty($tables[$t])
			&& !isset($tables[$t]['linkcheck_champs'])
			&& !empty($tables[$t]['rechercher_champs'])) {
			$tables[$t]['linkcheck_champs'] = [];
		}
	}
	return $tables;
}


/**
 * Déclaration des tables secondaires (liaisons)
 */
function linkcheck_declarer_tables_auxiliaires($tables) {

	$tables['spip_linkchecks_liens'] = [
		'field' => [
			'id_linkcheck'		=> "bigint(21) DEFAULT '0' NOT NULL",
			'id_objet'			=> "bigint(21) DEFAULT '0' NOT NULL",
			'objet'				=> "VARCHAR(25) DEFAULT '' NOT NULL",
			'publie'				=> "VARCHAR(3) DEFAULT '' NOT NULL"
		],
		'key' => [
			'PRIMARY KEY'		=> 'id_linkcheck,id_objet,objet',
			'KEY id_linkcheck'	=> 'id_linkcheck'
		]
	];

	return $tables;
}
