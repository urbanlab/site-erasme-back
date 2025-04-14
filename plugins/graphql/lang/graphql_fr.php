<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(
	// A
	'actif_oui' => 'Actif',
	'actif_non' => 'Inactif',
	'activer' => 'Activer',
	'activer_api_key' => 'Clé API',
	'aucune' => 'Aucune',
	'autoriser' => 'Autoriser',
	'autoriser_get' => 'Autoriser les requêtes GET',
	'autoriser_get_desc' => 'Non recommandé',

	// C
	"cfg_api" => 'Sélectionner les collections à exposer sur l\'API',
	'cfg_collections_liees' => 'Sélectionner les collections liées à exposer',
	'cfg_general' => 'Réglagles généraux',
	'cfg_jeton' => 'Sécurisez votre API',
	'cfg_meta' => 'Sélectionner les metas à exposer sur l\'API',
	'champs_objet' => 'Sélectionner les champs que vous souhaitez exposer sur l\'API',
	'collection' => 'Collection',
	'copier' => 'Copier dans le presse-papier',

	// D
	'debug_titre' => 'Mode Debug',
	'debug_desc' => 'Afficher les retours d\'erreur',
	'desactiver' => 'Désactiver',
	'desc_arg_id' => 'ID de l\'objet',
	'desc_arg_lang' => 'Langue de la recherche',
	'desc_arg_page' => 'Page retournée pour la pagination',
	'desc_arg_pagination' => 'Nombre d\'objets maximal retournés',
	'desc_arg_texte' => 'Votre recherche',
	'desc_arg_where' => 'Clause WHERE de votre requête sous forme de tableau (AND)',
	'desc_query_collection' => 'Retourne une collection d\'objets',
	'desc_query_collections' => 'Retourne la liste des collections disponibles',
	'desc_query_getmeta' => 'Retourne les métas autorisées',
	'desc_query_objet' => 'Retourne un objet',
	'desc_query_recherche' => 'Résultats de recherche sur les objets',
	'desc_type_base' => 'Type de base permettant de partager des champs',
	'desc_type_collection' => 'Énumération des collections disponibles',
	'desc_type_collection_pagination' => 'Résultats d\'une liste d\'objets et de sa pagination',
	'desc_type_date' => 'Format : AAAA-MM-JJ HH:MM:SS',
	'desc_type_interface' => 'Un objet éditorial de SPIP',
	'desc_type_metalist' => 'Métas autorisées',
	'desc_type_objet' => 'Un objet',
	'desc_type_pagination' => 'Représente la page courante et le nombre de pages',
	'desc_type_query' => 'Liste des requêtes disponibles',
	'desc_type_recherchePagination' => 'Résultats de recherche',
	'desc_type_searchresult' => 'Type UNION permettant de retourner n\'importe quel type d\'objet',

	// E
	'erreur_clause_where' => 'Erreur dans la clause where',
	'erreur_jeton' => 'Mauvais Jeton API',
	'erreur_pas_requete' => 'Aucune requête',
	'erreur_schema' => 'Le schéma d\'introspection est absent. Veuillez exposer des données',

	// G
	'generate' => 'Re-générer',

	// O
	'options_spip' => 'Options SPIP',

	// P
	'pagination' => 'Pagination',
	'pagination_recherche' => 'Pagination pour la recherche',
	'profondeur_limit' => 'Profondeur maximale de la requête',
	'profondeur_limit_desc' => 'Protège contre les requêtes malicieuses (0 : désactivé)',

	// S
	'schema_disabled' => 'Désactiver le schéma d\'instrospection',
	'schema_disabled_desc' => 'Empêche les clients de parcourir le schéma',
	'select_all' => 'Tout sélectionner',

	// T
	'titre_configurer_jeton' => 'Jeton API',
	'titre_graphiql' => 'GraphiQL IDE',
	'titre_graphql_configurer' => 'Réglages',
	'titre_graphql_expositions' => 'Données à exposer',
	'titre_graphql_jeton' => 'Jeton API',
	'titre_page_configurer_graphql' => 'GraphQL',
	'type_meta_desc' => 'Retourne les metas autorisées'
);
