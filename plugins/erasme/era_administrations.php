<?php
if (!defined("_ECRIRE_INC_VERSION")) { return;
}

include_spip('inc/cextras');
include_spip('era_cextras.php');


/**
 * Déclaration des actions nécessaires en fonction de la version du schema déclarée dans paquet.xml.
 *
 * @param $nom_meta_base_version
 * @param $version_cible
 */
function era_upgrade($nom_meta_base_version,$version_cible) {

    $maj = array();
    // N.B. : La fonction cextras_api_upgrade se trouve dans le module « Champ extra ».
    cextras_api_upgrade(era_declarer_champs_extras(), $maj['create']);

    // Chaque màj nécessitant une action particulière est notifiée ici :
    $maj['1.0.1'] = array(
    array('era_ajouter_mots_cles'),
    );
    $maj['1.0.2'] = array(
    array('era_ajouter_mots_cles_champs'),
    );
    cextras_api_upgrade(era_declarer_champs_extras(), $maj['1.0.3']);
    $maj['1.0.4'] = array(
    array('era_update_couleurs_onglets'),
    );
    $maj['1.0.5'] = array(
    array('era_ajouter_mots_cles_champs'),
    );
    cextras_api_upgrade(era_declarer_champs_extras(), $maj['1.0.6']);
    cextras_api_upgrade(era_declarer_champs_extras(), $maj['1.0.7']);
    cextras_api_upgrade(era_declarer_champs_extras(), $maj['1.1.1']);


    include_spip('base/upgrade');
    maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function era_ajouter_mots_cles()
{
    spip_log('Ajout des mots-cles');

    // ////////////////////////////////////////
    //
    // Groupe de mots-clés 'Entreprises'

    if (!$id_groupe = sql_getfetsel('id_groupe', "spip_groupes_mots", "titre='Entreprises'")) {
        $id_groupe = sql_insertq(
            'spip_groupes_mots', array(
            'titre' => 'Entreprises',
            'unseul' => 'non',
            'tables_liees' => 'articles',
            'minirezo' => 'oui',
            'comite' => 'non',
            'forum' => 'non'
            )
        );
    }

    // Mots-clés enfants

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Grand Lyon' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Grand Lyon','id_groupe' => $id_groupe,'type' => 'Entreprises'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Theoriz' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Theoriz','id_groupe' => $id_groupe,'type' => 'Entreprises'));
    }


    // ////////////////////////////////////////
    //
    // Groupe de mots-clés 'Méthodologies'

    if (!$id_groupe = sql_getfetsel("id_groupe", "spip_groupes_mots", "titre='Méthodologies'")) {
        $id_groupe = sql_insertq(
            'spip_groupes_mots', array(
            'titre' => 'Méthodologies',
            'unseul' => 'non',
            'tables_liees' => 'articles',
            'minirezo' => 'oui',
            'comite' => 'non',
            'forum' => 'non'
            )
        );
    }

    // Mots-clés enfants

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Collaboration' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Collaboration','id_groupe' => $id_groupe,'type' => 'Méthodologies'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Compétition' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Compétition','id_groupe' => $id_groupe,'type' => 'Méthodologies'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Médiation' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Médiation','id_groupe' => $id_groupe,'type' => 'Méthodologies'));
    }

    // ////////////////////////////////////////
    //
    // Groupe de mots-clés 'Partenaires'

    if (!$id_groupe = sql_getfetsel("id_groupe", "spip_groupes_mots", "titre='Partenaires'")) {
        $id_groupe = sql_insertq(
            'spip_groupes_mots', array(
            'titre' => 'Partenaires',
            'unseul' => 'non',
            'tables_liees' => 'articles',
            'minirezo' => 'oui',
            'comite' => 'non',
            'forum' => 'non'
            )
        );
    }

    // Mots-clés enfants

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='IFE' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'IFE','id_groupe' => $id_groupe,'type' => 'Partenaires'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Planétarium de Vaulx-en-Velin' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Planétarium de Vaulx-en-Velin','id_groupe' => $id_groupe,'type' => 'Partenaires'));
    }

    // ////////////////////////////////////////
    //
    // Groupe de mots-clés 'Technologies'

    if (!$id_groupe = sql_getfetsel("id_groupe", "spip_groupes_mots", "titre='Technologies'")) {
        $id_groupe = sql_insertq(
            'spip_groupes_mots', array(
            'titre' => 'Technologies',
            'unseul' => 'non',
            'tables_liees' => 'articles',
            'minirezo' => 'oui',
            'comite' => 'non',
            'forum' => 'non'
            )
        );
    }

    // Mots-clés enfants

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Gestionnaire de contenu sémantique' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Gestionnaire de contenu sémantique','id_groupe' => $id_groupe,'type' => 'Technologies'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Multitouch' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Multitouch','id_groupe' => $id_groupe,'type' => 'Technologies'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='RFID' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'RFID','id_groupe' => $id_groupe,'type' => 'Technologies'));
    }

    // ////////////////////////////////////////
    //
    // Groupe de mots-clés 'Usages'

    if (!$id_groupe = sql_getfetsel("id_groupe", "spip_groupes_mots", "titre='Usages'")) {
        $id_groupe = sql_insertq(
            'spip_groupes_mots', array(
            'titre' => 'Usages',
            'unseul' => 'non',
            'tables_liees' => 'articles',
            'minirezo' => 'oui',
            'comite' => 'non',
            'forum' => 'non'
            )
        );
    }

    // Mots-clés enfants

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Objet numérique' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Objet numérique','id_groupe' => $id_groupe,'type' => 'Usages'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Production inversée' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Production inversée','id_groupe' => $id_groupe,'type' => 'Usages'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", 'titre="Projet d\'établissement"'." AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Projet d\'établissement','id_groupe' => $id_groupe,'type' => 'Usages'));
    }

    // ////////////////////////////////////////
    //
    // Groupe de mots-clés 'Utilisateurs'

    if (!$id_groupe = sql_getfetsel("id_groupe", "spip_groupes_mots", "titre='Utilisateurs'")) {
        $id_groupe = sql_insertq(
            'spip_groupes_mots', array(
            'titre' => 'Utilisateurs',
            'unseul' => 'non',
            'tables_liees' => 'articles',
            'minirezo' => 'oui',
            'comite' => 'non',
            'forum' => 'non'
            )
        );
    }

    // Mots-clés enfants

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Collège François Truffaut' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Collège François Truffaut','id_groupe' => $id_groupe,'type' => 'Utilisateurs'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Collège Jean Castagne' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Collège Jean Castagne','id_groupe' => $id_groupe,'type' => 'Utilisateurs'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='Collège Philippe Delorme' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'Collège Philippe Delorme','id_groupe' => $id_groupe,'type' => 'Utilisateurs'));
    }
}

function era_ajouter_mots_cles_champs()
{


    // ////////////////////////////////////////
    //
    // Groupe de mots-clés 'Champs'

    if (!$id_groupe = sql_getfetsel("id_groupe", "spip_groupes_mots", "titre='Champs'")) {
        $id_groupe = sql_insertq(
            'spip_groupes_mots', array(
            'titre' => 'Champs',
            'unseul' => 'non',
            'tables_liees' => 'articles,rubriques',
            'minirezo' => 'oui',
            'comite' => 'non',
            'forum' => 'non'
            )
        );
    }

    // Mots-clés enfants

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='musees' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'musees','id_groupe' => $id_groupe,'type' => 'Champs'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='education' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'education','id_groupe' => $id_groupe,'type' => 'Champs'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='solidarite' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'solidarite','id_groupe' => $id_groupe,'type' => 'Champs'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='erasme' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'erasme','id_groupe' => $id_groupe,'type' => 'Champs'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='veille' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'veille','id_groupe' => $id_groupe,'type' => 'Champs'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='projets' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'projets','id_groupe' => $id_groupe,'type' => 'Champs'));
    }

    if (!$id_mot = sql_getfetsel("id_mot", "spip_mots", "titre='lab' AND id_groupe=$id_groupe")) {
        $id = sql_insertq('spip_mots', array('titre' => 'lab','id_groupe' => $id_groupe,'type' => 'Champs'));
    }
}

function era_update_couleurs_onglets()
{

    if (sql_getfetsel("couleur_tab", "spip_rubriques", "titre='Education'") == '') {
        sql_updateq('spip_rubriques', array('couleur_tab' => '#A6D011'), 'titre="Education"');
    }

    if (sql_getfetsel("couleur_tab", "spip_rubriques", "titre='Musées'") == '') {
        sql_updateq('spip_rubriques', array('couleur_tab' => '#48C1D9'), 'titre="Musées"');
    }

    if (sql_getfetsel("couleur_tab", "spip_rubriques", "titre='Erasme'") == '') {
        sql_updateq('spip_rubriques', array('couleur_tab' => '#8269E1'), 'titre="Erasme"');
    }

    if (sql_getfetsel("couleur_tab", "spip_rubriques", "titre='Seniors'") == '') {
        sql_updateq('spip_rubriques', array('couleur_tab' => '#FFAA09'), 'titre="Seniors"');
    }

    if (sql_getfetsel("couleur_tab", "spip_rubriques", "titre='Technologies'") == '') {
        sql_updateq('spip_rubriques', array('couleur_tab' => '#FF3274'), 'titre="Technologies"');
    }
}
