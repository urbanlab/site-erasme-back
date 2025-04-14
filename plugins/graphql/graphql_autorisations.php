<?php

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

// Obligatoire
function graphql_autoriser() {
}

function autoriser_configurergraphql_menu_dist($faire, $type, $id, $qui, $opt) {
    return ($qui['webmestre'] == 'oui');
}

function autoriser_graphql_dist($faire, $type, $id, $qui, $opt) {
    return ($qui['webmestre'] == 'oui');
}
