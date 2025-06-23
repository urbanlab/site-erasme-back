<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function era_declarer_champs_extras($champs = []) {

	$champs['spip_articles']['isprototype'] = [
		'saisie' => 'checkbox',
		'options' => [
			'nom' => 'isprototype',
			'sql' => 'varchar(2) DEFAULT \'\' NOT NULL',
			'data' => [
				'on' => _L('Est un prototype ?'),
			],
		],
	];
	$champs['spip_articles']['description_title'] = [
		'saisie' => 'input',
		'options' => [
			'nom' => 'description_title',
			'label' => 'Titre de la description principale',
			'type' => 'text',
			'size' => '40',
			'autocomplete' => 'defaut',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher' => 'on',
			'rechercher_ponderation' => '8',
			'defaut' => 'Ex. : Tous à table !',
			'afficher_si' => '@isprototype@=="on"',
		],
	];

	$champs['spip_articles']['description'] = [
		'saisie' => 'textarea',
		'options' => [
			'nom' => 'description',
			'label' => 'Contenu de la description principale',
			'rows' => '5',
			'cols' => '40',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher' => 'on',
			'rechercher_ponderation' => '2',
			'defaut' => '',
			'afficher_si' => '@isprototype@=="on"',
		],
	];

	$champs['spip_articles']['description_title_second'] = [
		'saisie' => 'input',
		'options' => [
			'nom' => 'description_title_second',
			'label' => 'Titre de la description n°2',
			'type' => 'text',
			'size' => '40',
			'autocomplete' => 'defaut',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher' => 'on',
			'rechercher_ponderation' => '8',
			'defaut' => 'Ex. : Comment ça marche ?',
			'afficher_si' => '@isprototype@=="on"',
		],
	];

	$champs['spip_articles']['description_second'] = [
		'saisie' => 'textarea',
		'options' => [
			'nom' => 'description_second',
			'label' => 'Contenu de la description n°2',
			'rows' => '5', 'cols' => '40',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher' => 'on',
			'rechercher_ponderation' => '2',
			'defaut' => '',
			'afficher_si' => '@isprototype@=="on"',
		],
	];

	$champs['spip_articles']['chiffres_cles'] = [
		'saisie' => 'textarea',
		'options' => [
			'nom' => 'chiffres_cles',
			'label' => 'Chiffres clés',
			'explication' => 'Une ligne par chiffre clé, sous la forme "123 jours de travail sur ce projet" ; précéder les lignes souhaitées dans la version print d\'un crochet ">" sous la forme : "> 123 établissements"',
			'rows' => '6',
			'cols' => '40',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher_ponderation' => '2',
			'defaut' => 'Ex. :
> 2 ans d\'expérimentation
8 établissements dont 4 écoles et 4 collèges
844 fiches et 8 scénarios produits',
			'afficher_si' => '@isprototype@=="on"',
		],
	];
	/* Renommer ce champ en description_title_third */
	$champs['spip_articles']['description_lateral_title'] = [
		'saisie' => 'input',
		'options' => [
			'nom' => 'description_lateral_title',
			'label' => 'Titre de la description n°3',
			'type' => 'text',
			'size' => '40',
			'autocomplete' => 'defaut',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher' => 'on',
			'rechercher_ponderation' => '8',
			'defaut' => '',
			'afficher_si' => '@isprototype@=="on"',
		],
	];
	/* Renommer ce champ en description_third */
	$champs['spip_articles']['description_lateral'] = [
		'saisie' => 'textarea',
		'options' => [
			'nom' => 'description_lateral',
			'label' => 'Contenu de la description n°3',
			'rows' => '5',
			'cols' => '40',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher' => 'on',
			'rechercher_ponderation' => '2',
			'defaut' => '',
			'afficher_si' => '@isprototype@=="on"',
		],
	];

	$champs['spip_articles']['developpement'] = [
		'saisie' => 'textarea',
		'options' => [
			'nom' => 'developpement',
			'label' => 'Stade de développement',
			'explication' => 'Indiquer chaque phase de développement sous la forme "2016 : conception" ; une phase par ligne ; préciser la phase courante en précédant la ligne d\'un crochet ">" sous la forme "> 2018 : incubation"',
			'rows' => '5',
			'cols' => '40',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher_ponderation' => '2',
			'defaut' => 'Ex. :	2011 : Conception	2012 : Expérimentation	> 2013 : Incubation	2014 : Externalisation',
			'afficher_si' => '@isprototype@=="on"',
		],
	];

	$champs['spip_articles']['fieldset_1'] = [
		'saisie' => 'fieldset',
		'options' => [
			'nom' => 'fieldset_1',
			'label' => 'Descriptif technique',
			'explication' => 'Laisser un champ vide pour ne pas l\'afficher dans la fiche projet',
			'afficher_si' => '@isprototype@=="on"',
		],
		'saisies' => [
			'descr_tech_technique' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'descr_tech_technique',
					'label' => 'Type technique',
					'type' => 'text',
					'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			],
			'descr_tech_devices' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'descr_tech_devices',
					'label' => 'Devices / Compatibilité',
					'type' => 'text',
					'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			],
			'descr_tech_framework' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'descr_tech_framework',
					'label' => 'Framework',
					'type' => 'text',
					'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			], 'descr_tech_depot' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'descr_tech_depot',
					'label' => 'Dépôt',
					'type' => 'text',
					'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			],
			'descr_tech_licence' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'descr_tech_licence',
					'label' => 'Licence',
					'type' => 'text',
					'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
				],
			],
		],
	];

	$champs['spip_articles']['fieldset_3'] = [
		'saisie' => 'fieldset',
		'options' => [
			'nom' => 'fieldset_3',
			'label' => 'Informations',
			'afficher_si' => '@isprototype@=="on"',
		],
		'saisies' => [
			'information_site_web' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'information_site_web',
					'label' => 'Site web',
					'type' => 'text',	'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
				'verifier' => [
					'type' => 'url',
					'options' => [
						'mode' => 'protocole_seul',
						'type_protocole' => 'web',
					],
				],
			], 'information_mailing' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'information_mailing',
					'label' => 'Mailing liste',
					'type' => 'text',
					'size' => '40',
					'autocomplete' =>
					'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			], 'information_communaute_utilisateurs' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'information_communaute_utilisateurs',
					'label' => 'Communauté d\'utilisateurs',
					'type' => 'text',
					'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			], 'information_entreprises' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'information_entreprises',
					'label' => 'Entreprises labellisées',
					'type' => 'text',
					'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			],
		],
	];

	$champs['spip_articles']['fieldset_5'] = [
		'saisie' => 'fieldset',
		'options' => [
			'nom' => 'fieldset_5',
			'label' => 'Contenus supplémentaires',
			'afficher_si' => '@isprototype@=="on"',
		],
		'saisies' => [
			'description_title_third' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'description_title_third',
					'label' => 'Intitulé de la description n°4',
					'type' => 'text',
					'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			],
			'description_fourth' => [
				'saisie' => 'textarea',
				'options' => [
					'nom' => 'description_third',
					'label' => 'Contenu de la description n°4',
					'rows' => '5',	'cols' => '40',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			],
			'media' => [
				'saisie' => 'textarea',
				'options' => [
					'nom' => 'media',
					'label' => 'Média',
					'explication' => 'Peut inclure du code HTML interprété, un code d\'intégration embed d\'une vidéo, etc.',
					'rows' => '5',
					'cols' => '40',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			],
			'id_linked_rub' => [
				'saisie' => 'input',
				'options' => [
					'nom' => 'id_linked_rub',
					'label' => 'Id de la rubrique liée au projet',
					'type' => 'text',	'size' => '40',
					'autocomplete' => 'defaut',
					'sql' => 'text DEFAULT \'\' NOT NULL',
					'rechercher_ponderation' => '2',
				],
			],
		],
	];

	// Table : spip_rubriques
	$champs['spip_rubriques']['ordre_menu'] = [
		'saisie' => 'input',
		'options' => [
			'nom' => 'ordre_menu',
			'label' => 'Position de l\'onglet dans le menu principal',
			'explication' => 'Indiquer un chiffre (1, 2, 3…) ou laisser vide',
			'type' => 'text',
			'maxlength' => '2',
			'size' => '40',
			'autocomplete' => 'defaut',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'defaut' => '',
			'rechercher_ponderation' => '2',
		],
		'verifier' => [
			'type' => 'entier',
			'options' => ['min' => '1'],
		],
	];

	$champs['spip_rubriques']['couleur_tab'] = [
		'saisie' => 'input',
		'options' => [
			'nom' => 'couleur_tab',
			'label' => 'Couleur de la rubrique',
			'explication' => 'Entrer une valeur hexadécimale. Ci-dessous les couleurs au choix.<style>.colorPicker{display:block;color:white;font-weight:bold;font-size:12px;padding:3px 5px;}</style>
<div style="font-size:10px">
<div class="colorPicker" style="background:#8269E1">#8269E1</div>
<div class="colorPicker" style="background:#48C1D9">#48C1D9</div>
<div class="colorPicker" style="background:#A6D011">#A6D011</div>
<div class="colorPicker" style="background:#FFAA09">#FFAA09</div>
<div class="colorPicker" style="background:#FF3274">#FF3274</div>
<div class="colorPicker" style="background:#654665">#654665</div>
<div class="colorPicker" style="background:#d2513c">#D2513C</div>
</div>',
			'type' => 'text',
			'size' => '40',
			'autocomplete' => 'defaut',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher_ponderation' => '2',
		],
	];

	// Table : spip_documents
	$champs['spip_documents']['type'] = [
		'saisie' => 'selection',
		'options' => [
			'nom' => 'type',
			'label' => 'Identité du document',
			'datas' => [
				'type_fonctionnement' => 'Description 1',
				'type_description2' => 'Description 2',
				'type_description3' => 'Description 3',
				'type_diaporama' => 'Diaporama',
				'type_image_principale' => 'Image d\'en-tête',
			],
			'defaut' => 'type_diaporama',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher_ponderation' => '2',
			'afficher_si' => '@isprototype@=="on"',
		],
	];

	/*  Table auteur */
	$champs['spip_auteurs']['auteur_compte_twitter'] = [
		'saisie' => 'input',
		'options' => [
			'nom' => 'auteur_compte_twitter',
			'label' => 'Auteur compte Twitter',
			'type' => 'text',
			'size' => '40',
			'autocomplete' => 'defaut',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher_ponderation' => '2',
		],
	];
	$champs['spip_auteurs']['auteur_compte_linkedin'] = [
		'saisie' => 'input',
		'options' => [
			'nom' => 'auteur_compte_linkedin',
			'label' => 'Auteur compte LinkedIn',
			'type' => 'text', 'size' => '40',
			'autocomplete' => 'defaut',
			'sql' => 'text DEFAULT \'\' NOT NULL',
			'rechercher_ponderation' => '2',
		],
	];
	return $champs;
}
