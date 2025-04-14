<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_once _DIR_PLUGIN_GRAPHQL . 'vendor/autoload.php';

/**
 * Fonction d'installation et de mise à jour du plugin
 *
 * Vous pouvez :
 *
 * - créer la structure SQL,
 * - insérer du pre-contenu,
 * - installer des valeurs de configuration,
 * - mettre à jour la structure SQL
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
 **/
function graphql_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();
	// Création de la table qui stockera la config du plugin
	$maj['create'][] = array('installer_table_meta', 'meta_graphql');

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Fonction de désinstallation du plugin.
 *
 * Vous devez :
 *
 * - nettoyer toutes les données ajoutées par le plugin et son utilisation
 * - supprimer les tables et les champs créés par le plugin.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @return void
 **/
function graphql_vider_tables($nom_meta_base_version) {
	// Suppression de la table qui stocke la config du plugin
	sql_drop_table('spip_meta_graphql');
	// Effacement de la version du schema du plugin dans la table spip_meta
	effacer_meta($nom_meta_base_version);
}
