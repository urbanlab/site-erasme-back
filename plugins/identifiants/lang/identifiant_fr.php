<?php
// This is a SPIP language file -- Ceci est un fichier langue de SPIP

return [

	// B
	'bouton_identifiant_changer' => 'Changer',
	'bouton_identifiant_modifier' => 'Modifier',
	'bouton_identifiant_definir' => 'Définir un identifiant',
	'bouton_confirmer' => 'Oui, poursuivre',
	'bouton_attribuer_identifiant' => 'Attribuer <strong>@identifiant@</strong>',
	'bouton_attribuer_toggle' => 'Choisir un identifiant',


	// C
	'cfg_titre_parametrages' => 'Paramétrages',
	'champ_cfg_objets_label' => 'Contenus identifiables',
	'champ_cfg_objets_explication' => 'Choix des types de contenus où activer les identifiants.',
	'champ_cfg_unicite_label' => 'Unicité',
	'champ_cfg_unicite_label_case' => 'Interdire la réutilisation des identifiants',
	'champ_cfg_unicite_explication' => 'Si cette option est cochée, un identifiant ne pourra être utilisé qu’une fois par type de contenu et par langue',
	'champ_identifiant_label' => 'Identifiant',
	'champ_objet_type_label' => 'Contenu',
	'champ_objet_lang_label' => 'Langue',
	'champ_objet_titre_label' => 'Titre',
	'champ_objets_label' => 'Contenus',
	'champ_date_label' => 'Date',
	'champ_identifiant_explication' => 'Caractères alphanumériques en minuscules ou un souligné <code>_</code>',

	// E
	'erreur_identifiant_format' => 'Format incorrect, suggestion : @slug@',
	'erreur_identifiant_doublon' => 'Cet identifiant est déjà utilisé par un même type de contenu',
	'erreur_identifiant_doublon_objet' => 'Cet identifiant est déjà utilisé par un même type de contenu : @objet@ n°@id_objet@',
	'erreur_identifiant_taille' => '@nb_max@ caractères au maximum (actuellement @nb@)',
	'explication_utiles' => 'Les identifiants suivants peuvent être attribués aux @objets@.',

	// F
	'filtre_tous' => 'Tous',

	// I
	'info_1_identifiant' => '1 identifiant',
	'info_aucun_identifiant' => 'Aucun identifiant',
	'info_nb_identifiants' => '@nb@ identifiants',
	'info_identifiants_objets_exclus' => 'Les contenus suivants ont nativement un champ « identifiant » et ne sont donc pas affichés',
	'info_identifiants_objets_manquants' => 'Les squelettes actuels du site suggèrent d\’activer les contenus suivants',

	// M
	'message_confirmer_suppression' => '@nb@ identifiant(s) vont être supprimé(s) suite à cette nouvelle configuration, êtes vous sûr⋅e ?',
	'message_ok_adapter_tables' => '@action@ de la colonne « identifiant » sur les tables suivantes : @tables@',
	'message_erreur_adapter_tables' => 'Echec @action@ de la colonne « identifiant » sur les tables suivantes : @tables@',

	// T
	'titre_page_configurer_identifiants' => 'Configuration des identifiants',
	'titre_identifiant' => 'Identifiant',
	'titre_identifiants' => 'Identifiants',
	'titre_utiles' => 'Identifiants utiles',

	// V
	'verifier_identifiant_titre' => 'Identifiant',
	'verifier_identifiant_description' => 'Vérifier que la chaîne de caractère est valable pour un identifiant informatique : chiffres, lettres latines non accentuées et le caractère "_"',
	'verifier_identifiant_unicite_label' => 'Vérifier l’unicité de l’identifiant (aucune autre utilisation par un même type de contenu)',
	'verifier_identifiant_unicite_objet_label' => 'Type de contenu',

];
