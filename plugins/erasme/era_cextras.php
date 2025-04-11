<?php
if (!defined("_ECRIRE_INC_VERSION")) { return;
}

function era_declarer_champs_extras($champs = array())
{

    // Table : spip_articles
    if (!is_array($champs['spip_articles'])) {
        $champs['spip_articles'] = array();
    }

    $champs['spip_articles']['description_title'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'description_title',
            'label' => 'Titre de la description principale',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher' => 'on',
            'rechercher_ponderation' => '8',
            'defaut' => 'Ex. : Tous à table !'
          ),
    );

    $champs['spip_articles']['description'] = array (
          'saisie' => 'textarea',
          'options' =>
          array (
            'nom' => 'description',
            'label' => 'Contenu de la description principale',
            'rows' => '5',
            'cols' => '40',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher' => 'on',
            'rechercher_ponderation' => '2',
            'defaut' => ''
          ),
    );

    $champs['spip_articles']['description_title_second'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'description_title_second',
            'label' => 'Titre de la description n°2',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher' => 'on',
            'rechercher_ponderation' => '8',
            'defaut' => 'Ex. : Comment ça marche ?'
          ),
    );

    $champs['spip_articles']['description_second'] = array (
          'saisie' => 'textarea',
          'options' =>
          array (
            'nom' => 'description_second',
            'label' => 'Contenu de la description n°2',
            'rows' => '5',
            'cols' => '40',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher' => 'on',
            'rechercher_ponderation' => '2',
            'defaut' => ''
          ),
    );

    $champs['spip_articles']['chiffres_cles'] = array (
          'saisie' => 'textarea',
          'options' =>
          array (
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
844 fiches et 8 scénarios produits'
          ),
    );
    /* Renommer ce champ en description_title_third */
    $champs['spip_articles']['description_lateral_title'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'description_lateral_title',
            'label' => 'Titre de la description n°3',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher' => 'on',
            'rechercher_ponderation' => '8',
            'defaut' => ''
          ),
    );
    /* Renommer ce champ en description_third */
    $champs['spip_articles']['description_lateral'] = array (
          'saisie' => 'textarea',
          'options' =>
          array (
            'nom' => 'description_lateral',
            'label' => 'Contenu de la description n°3',
            'rows' => '5',
            'cols' => '40',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher' => 'on',
            'rechercher_ponderation' => '2',
            'defaut' => ''
          ),
    );

    $champs['spip_articles']['developpement'] = array (
          'saisie' => 'textarea',
          'options' =>
          array (
            'nom' => 'developpement',
            'label' => 'Stade de développement',
            'explication' => 'Indiquer chaque phase de développement sous la forme "2016 : conception" ; une phase par ligne ; préciser la phase courante en précédant la ligne d\'un crochet ">" sous la forme "> 2018 : incubation"',
            'rows' => '5',
            'cols' => '40',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
            'defaut' => 'Ex. :
				2011 : Conception
				2012 : Expérimentation
				> 2013 : Incubation
				2014 : Externalisation'
          ),
    );

    $champs['spip_articles']['fieldset_1'] = array (
          'saisie' => 'fieldset',
          'options' =>
          array (
            'nom' => 'fieldset_1',
            'label' => 'Descriptif technique',
            'explication' => 'Laisser un champ vide pour ne pas l\'afficher dans la fiche projet',
          ),
          'saisies' =>
          array (
          ),
    );

    $champs['spip_articles']['descr_tech_technique'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'descr_tech_technique',
            'label' => 'Type technique',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['descr_tech_devices'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'descr_tech_devices',
            'label' => 'Devices / Compatibilité',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['descr_tech_framework'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'descr_tech_framework',
            'label' => 'Framework',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['descr_tech_depot'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'descr_tech_depot',
            'label' => 'Dépôt',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['descr_tech_licence'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'descr_tech_licence',
            'label' => 'Licence',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['fieldset_3'] = array (
          'saisie' => 'fieldset',
          'options' =>
          array (
            'nom' => 'fieldset_3',
            'label' => 'Informations',
          ),
          'saisies' =>
          array (
          ),
    );

    $champs['spip_articles']['information_site_web'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'information_site_web',
            'label' => 'Site web',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
          'verifier' =>
          array (
            'type' => 'url',
            'options' =>
            array (
              'mode' => 'protocole_seul',
              'type_protocole' => 'web',
            ),
          ),
    );

    $champs['spip_articles']['information_mailing'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'information_mailing',
            'label' => 'Mailing liste',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['information_communaute_utilisateurs'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'information_communaute_utilisateurs',
            'label' => 'Communauté d\'utilisateurs',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['information_entreprises'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'information_entreprises',
            'label' => 'Entreprises labellisées',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['fieldset_5'] = array (
          'saisie' => 'fieldset',
          'options' =>
          array (
            'nom' => 'fieldset_5',
            'label' => 'Contenus supplémentaires',
          ),
          'saisies' =>
          array (
          ),
    );

    $champs['spip_articles']['description_title_third'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'description_title_third',
            'label' => 'Intitulé de la description n°4',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['description_fourth'] = array (
          'saisie' => 'textarea',
          'options' =>
          array (
            'nom' => 'description_third',
            'label' => 'Contenu de la description n°4',
            'rows' => '5',
            'cols' => '40',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['media'] = array (
          'saisie' => 'textarea',
          'options' =>
          array (
            'nom' => 'media',
            'label' => 'Média',
            'explication' => 'Peut inclure du code HTML interprété, un code d\'intégration embed d\'une vidéo, etc.',
            'rows' => '5',
            'cols' => '40',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    $champs['spip_articles']['id_linked_rub'] = array (
          'saisie' => 'input',
          'options' =>
          array (
            'nom' => 'id_linked_rub',
            'label' => 'Id de la rubrique liée au projet',
            'type' => 'text',
            'size' => '40',
            'autocomplete' => 'defaut',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );

    // Table : spip_rubriques
    if (!is_array($champs['spip_rubriques'])) {
        $champs['spip_rubriques'] = array();
    }


    $champs['spip_rubriques']['ordre_menu'] = array (
          'saisie' => 'input',
          'options' => array (
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
          ),
          'verifier' =>
          array (
            'type' => 'entier',
            'options' =>
            array (
              'min' => '1',
            ),
          ),
    );


    $champs['spip_rubriques']['couleur_tab'] = array (
          'saisie' => 'input',
          'options' =>
          array (
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
          ),
    );


    // Table : spip_documents
    if (!is_array($champs['spip_documents'])) {
        $champs['spip_documents'] = array();
    }

    $champs['spip_documents']['type'] = array (
          'saisie' => 'selection',
          'options' =>
          array (
            'nom' => 'type',
            'label' => 'Identité du document',
            'datas' =>
            array (
              'type_fonctionnement' => 'Description 1',
              'type_description2' => 'Description 2',
              'type_description3' => 'Description 3',
              'type_diaporama' => 'Diaporama',
              'type_image_principale' => 'Image d\'en-tête',
            ),
            'defaut' => 'type_diaporama',
            'sql' => 'text DEFAULT \'\' NOT NULL',
            'rechercher_ponderation' => '2',
          ),
    );


    /*  Table auteur */
    $champs['spip_auteurs']['auteur_compte_twitter'] = array (
    'saisie' => 'input',
    'options' =>
    array (
          'nom' => 'auteur_compte_twitter',
          'label' => 'Auteur compte Twitter',
          'type' => 'text',
          'size' => '40',
          'autocomplete' => 'defaut',
          'sql' => 'text DEFAULT \'\' NOT NULL',
          'rechercher_ponderation' => '2',
    ),
    );
    $champs['spip_auteurs']['auteur_compte_linkedin'] = array (
    'saisie' => 'input',
    'options' =>
    array (
          'nom' => 'auteur_compte_linkedin',
          'label' => 'Auteur compte LinkedIn',
          'type' => 'text',
          'size' => '40',
          'autocomplete' => 'defaut',
          'sql' => 'text DEFAULT \'\' NOT NULL',
          'rechercher_ponderation' => '2',
    ),
    );
    return $champs;
}
