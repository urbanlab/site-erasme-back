<?php


if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Un simple formulaire de config,
 * on a juste à déclarer les saisies.
 * @return array
 **/
function formulaires_configurer_urls_par_numero_saisies_dist(): array {
	// $saisies est un tableau décrivant les saisies à afficher dans le formulaire de configuration
	$saisies = [
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'objets',
				'label' => '<:urls_par_numero:configurer_objets:>',
				'data' => [
					'article' => '<:urls_par_numero:configurer_article:>',
					'formulaire' => '<:urls_par_numero:configurer_formulaire:>'
				],
				'conteneur_classe' => 'pleine_largeur'
			]
		]
	];
	return $saisies;
}
