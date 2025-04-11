<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}


/**
 * Déclaration des alias de tables et filtres automatiques de champs
 */
function mailsubscribers_declarer_tables_interfaces($interfaces) {

	$interfaces['table_des_tables']['mailsubscribers'] = 'mailsubscribers';
	$interfaces['table_des_tables']['mailsubscribinglists'] = 'mailsubscribinglists';
	$interfaces['table_des_tables']['mailsubscriptions_optins'] = 'mailsubscriptions_optins';

	$interfaces['table_des_traitements']['TITRE_PUBLIC'][] = _TRAITEMENT_TYPO;

	return $interfaces;
}


/**
 * Déclaration des objets éditoriaux
 */
function mailsubscribers_declarer_tables_objets_sql($tables) {

	$tables['spip_mailsubscribers'] = [
		'type' => 'mailsubscriber',
		'page' => '',
		'principale' => 'oui',
		'field' => [
			'id_mailsubscriber' => 'bigint(21) NOT NULL',
			'email' => "varchar(255) NOT NULL DEFAULT ''",
			'nom' => "text NOT NULL DEFAULT ''",
			'optin' => "text NOT NULL DEFAULT ''",
			'date' => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'statut' => "varchar(20)  DEFAULT 'prepa' NOT NULL",
			'jeton' => "char(25)  DEFAULT '' NOT NULL",
			'lang' => "VARCHAR(10) NOT NULL DEFAULT ''",
			'invite_email_from' => "text NOT NULL DEFAULT ''",
			'invite_email_text' => "text NOT NULL DEFAULT ''",
			'maj' => 'TIMESTAMP'
		],
		'key' => [
			'PRIMARY KEY' => 'id_mailsubscriber',
			'UNIQUE email' => 'email(255)',
			'KEY lang' => 'lang',
			'KEY statut' => 'statut',
		],
		'titre' => 'email AS titre, lang AS lang',
		'date' => 'date',
		'champs_editables' => ['email', 'nom', 'lang'],
		'champs_versionnes' => ['email', 'nom', 'lang'],
		'rechercher_champs' => ['email' => 1, 'nom' => 1],
		'tables_jointures' => [
			'mailsubscriptions',
			'mailsubscribinglists',
		],
		// le statut de cette table ne pilote pas les abonnements mais les reflete
		// quand un abonnement de spip_mailsubscriptions il est mis a jour pour donner une info globale
		// (cet abonne n'est plus abonne a rien, est en attente de confirmation de son email, est ok)
		'statut_textes_instituer' => [
			'prepa' => 'mailsubscriber:texte_statut_pas_encore_inscrit',
			'prop' => 'mailsubscriber:texte_statut_en_attente_confirmation',
			'valide' => 'mailsubscriber:texte_statut_valide',
			'refuse' => 'mailsubscriber:texte_statut_refuse',
			'poubelle' => 'texte_statut_poubelle',
		],
		'statut_images' => [
			'prepa' => 'puce-preparer-8.png',
			'prop' => 'puce-proposer-8.png',
			'valide' => 'puce-publier-8.png',
			'refuse' => 'puce-refuser-8.png',
			'poubelle' => 'puce-supprimer-8.png',
		],
		'statut_titres' => [
			'prepa' => 'mailsubscriber:info_statut_prepa',
			'prop' => 'mailsubscriber:info_statut_prop',
			'valide' => 'mailsubscriber:info_statut_valide',
			'refuse' => 'mailsubscriber:info_statut_refuse',
			'poubelle' => 'mailsubscriber:info_statut_poubelle',
		],

		'statut' => [
			[
				'champ' => 'statut',
				'publie' => 'valide',
				'previsu' => 'valide,prop,prepa',
				'exception' => ['statut', 'tout']
			]
		],
		'texte_changer_statut' => 'mailsubscriber:texte_changer_statut_mailsubscriber',

	];

	$tables['spip_mailsubscribinglists'] = [
		'type' => 'mailsubscribinglist',
		'page' => '',
		'principale' => 'oui',
		'field' => [
			'id_mailsubscribinglist' => 'bigint(21) NOT NULL',
			'identifiant' => "varchar(255) NOT NULL DEFAULT ''",
			'titre' => "text NOT NULL DEFAULT ''",
			'titre_public' => "text NOT NULL DEFAULT ''",
			'descriptif' => "text DEFAULT '' NOT NULL",
			'adresse_envoi_nom' => "text DEFAULT '' NOT NULL",
			'adresse_envoi_email' => "text DEFAULT '' NOT NULL",
			'date' => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'statut' => "varchar(20)  DEFAULT 'prepa' NOT NULL",
			'segments' => "text DEFAULT '' NOT NULL",
			'maj' => 'TIMESTAMP'
		],
		'key' => [
			'PRIMARY KEY' => 'id_mailsubscribinglist',
			'UNIQUE identifiant' => 'identifiant(255)',
			'KEY statut' => 'statut',
		],
		'titre' => 'titre',
		'date' => 'date',
		'champs_editables' => ['identifiant', 'titre', 'titre_public', 'descriptif', 'anonyme', 'date', 'statut', 'adresse_envoi_nom', 'adresse_envoi_email'],
		'champs_versionnes' => ['identifiant', 'titre', 'titre_public', 'descriptif', 'anonyme', 'adresse_envoi_nom', 'adresse_envoi_email'],
		'rechercher_champs' => ['identifiant' => 1, 'titre' => 2, 'titre_public' => 2, 'descriptif' => 1],
		'tables_jointures' => [
			'mailsubscriptions',
			'mailsubscribers',
		],
		'statut_textes_instituer' => [
			'ouverte' => 'mailsubscribinglist:texte_statut_ouverte',
			'fermee' => 'mailsubscribinglist:texte_statut_fermee',
			'poubelle' => 'texte_statut_poubelle',
		],
		'statut_images' => [
			'ouverte' => 'puce-publier-8.png',
			'fermee' => 'puce-refuser-8.png',
			'poubelle' => 'puce-supprimer-8.png',
		],
		'statut_titres' => [
			'ouverte' => 'mailsubscribinglist:info_statut_ouverte',
			'fermee' => 'mailsubscribinglist:info_statut_fermee',
			'poubelle' => 'mailsubscribinglist:info_statut_poubelle',
		],

		'statut' => [
			[
				'champ' => 'statut',
				'publie' => 'ouverte',
				'previsu' => 'ouverte,fermee',
				'exception' => ['statut', 'tout']
			]
		],
		'texte_changer_statut' => 'mailsubscribinglist:texte_changer_statut_mailsubscribinglist',

	];



	$tables['spip_mailsubscriptions_optins'] = [
		'type' => 'mailsubscriptions_optin',
		'page' => '',
		'principale' => 'oui',
		'field' => [
			'id_mailsubscriptions_optin' => 'bigint(21) NOT NULL',
			'id_mailsubscriber' => "bigint(21) DEFAULT '0' NOT NULL",
			'id_mailsubscribinglist' => "bigint(21) DEFAULT '0' NOT NULL",
			// date d’envoi de la demande d’optin, ou date de réception accepté / refusé
			'date' => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			// prepa : demande d’optin à envoyer
			// prop : demande d’optin envoyée
			// valide : optin accepté
			// refuse : optin refusé
			// outdated : la demande optin n’a pas été cliqué (ni acceptée, ni refusée) au bout de X temps
			'statut' => "varchar(20)  DEFAULT 'prepa' NOT NULL",
			'maj' => 'TIMESTAMP',
		],
		'key' => [
			'PRIMARY KEY' => 'id_mailsubscriptions_optin',
			'KEY id_mailsubscriber' => 'id_mailsubscriber',
			'KEY id_mailsubscribinglist' => 'id_mailsubscribinglist',
			'KEY statut' => 'statut'
		],
		'join' => [
			'id_mailsubscriptions_optin' => 'id_mailsubscriptions_optin',
			'id_mailsubscriber' => 'id_mailsubscriber',
			'id_mailsubscribinglist' => 'id_mailsubscribinglist',
		],
		'tables_jointures' => [
			'mailsubscripbinglists',
			'mailsubscribers',
		],
		'titre' => '',
		'date' => 'date',
		'rechercher_champs' => [
			// il faut au moins un champ pour la recherche directe pour que la recherche par jointure fonctionne
			'statut' => 1,
		],
		'rechercher_jointures' => [
			'mailsubscriber' => ['email' => 10],
		],

		'statut' => [],
		'statut_images' => [
			'prepa' => 'puce-preparer-8.png',
			'prop' => 'puce-proposer-8.png',
			'valide' => 'puce-publier-8.png',
			'refuse' => 'puce-refuser-8.png',
			'outdated' => 'puce-supprimer-8.png',
		],
		'statut_titres' => [
			'prepa' => 'mailsubscriptions_optin:info_statut_prepa',
			'prop' => 'mailsubscriptions_optin:info_statut_prop',
			'valide' => 'mailsubscriptions_optin:info_statut_valide',
			'refuse' => 'mailsubscriptions_optin:info_statut_refuse',
			'outdated' => 'mailsubscriptions_optin:info_statut_outdated',
		],
	];

	return $tables;
}


/**
 * Déclaration des tables secondaires (liaisons)
 *
 * @param array $tables
 * @return array
 */
function mailsubscribers_declarer_tables_auxiliaires($tables) {

	$tables['spip_mailsubscriptions'] = [
		'field' => [
			'id_mailsubscriber' => "bigint(21) DEFAULT '0' NOT NULL",
			'id_mailsubscribinglist' => "bigint(21) DEFAULT '0' NOT NULL",
			'id_segment' => "smallint DEFAULT '0' NOT NULL",
			// prop : en attente confirmation
			// valide : subscription active
			// refuse : desinscrit
			'statut' => "varchar(20)  DEFAULT 'prop' NOT NULL",
			// 0 : rien a faire
			// 1 : actualiser les segments de cette subscription
			//       qui sont en auto_update
			//       qui sont dans la meta mailsubscriptions_update_segments (tableau serialize)
			'actualise_segments' => "tinyint DEFAULT '0' NOT NULL",
			'maj' => 'TIMESTAMP',
		],
		'key' => [
			'PRIMARY KEY' => 'id_mailsubscriber,id_mailsubscribinglist,id_segment',
			'KEY id_mailsubscriber' => 'id_mailsubscriber',
			'KEY id_mailsubscribinglist' => 'id_mailsubscribinglist',
			'KEY id_segment' => 'id_segment',
			'KEY statut' => 'statut'
		]
	];

	return $tables;
}
