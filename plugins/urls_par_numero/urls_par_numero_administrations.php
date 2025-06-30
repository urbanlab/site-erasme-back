<?php

/**
 * Fichier gérant l'installation et désinstallation du plugin
 *
**/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Mise à jour
**/
function urls_par_numero_upgrade(string $nom_meta_base_version, string $version_cible): void {
	$maj = [
		'create' => [['urls_par_numero_depart']],
	];
	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Installation de la config minimal
**/
function urls_par_numero_depart(): void {
	include_spip('inc/config');
	ecrire_config('urls_par_numero/objets', ['article']);
}

/**
 * Suppression du plugin
 * @param string $nom_meta_base_version
**/
function urls_par_numero_vider_tables(string $nom_meta_base_version): void {
	include_spip('inc/meta');
	include_spip('inc/config');
	effacer_config('urls_par_numero');
	effacer_meta($nom_meta_base_version);
}
