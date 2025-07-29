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