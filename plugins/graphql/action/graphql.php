<?php

declare(strict_types=1);

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');
include_spip('graphql_fonctions');
include_once _DIR_PLUGIN_GRAPHQL . 'vendor/autoload.php';

use SPIP\GraphQL\SchemaSPIP;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\Type;
use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Error\DebugFlag;

// Point d'entrée de l'API GraphQL
function  action_graphql_dist() {
	try {
		// Vérification du jeton
		if (!graphql_verif_jeton(getallheaders()))
			throw new RuntimeException(_T('graphql:erreur_jeton'));

		// Construction du schéma
		// https://webonyx.github.io/graphql-php/schema-definition/#lazy-loading-of-types
		$schemaSPIP = new SchemaSPIP();
		if (empty($schemaSPIP->collections_autorisees)) throw new RuntimeException(_T('graphql:erreur_schema'));

		$schema = new Schema([
			'query' => $schemaSPIP->get('Query'),
			'typeLoader' => static fn (string $name): Type => $schemaSPIP->get($name),
		]);

		// Récupération de la requête
		$rawInput = file_get_contents('php://input');
		if ($rawInput === false) {
			throw new RuntimeException('Failed to get php://input');
		}

		if ($rawInput != '') {
			// Requêtes POST
			$input = json_decode($rawInput, true);
			$query = (is_array($input) && array_key_exists('query', $input)) ? $input['query'] : '';
			$variableValues = isset($input['variables']) ? $input['variables'] : null;
		} elseif (array_key_exists('query', $_GET)) {
			// Requêtes GET
			if (lire_config('/meta_graphql/securite/autoriser_get', ['non'])[0] == 'non')
				throw new RuntimeException('GET queries not allowed');
			$query = $_GET['query'];
		}

		if (!$query) throw new RuntimeException(_T('graphql:erreur_pas_requete'));

		// Préparation de la requête
		// https://webonyx.github.io/graphql-php/executing-queries/#using-facade-method

		// Permet d'utiliser le resolver par défaut
		// https://webonyx.github.io/graphql-php/data-fetching/#default-field-resolver
		$rootValue = '';

		// Contexte de la requête (pour passer des données communes à toutes les requêtes)
		$contexte = [
			'rootUrl' => lire_config("adresse_site"),
			'request' => $_REQUEST
		];

		// Limitation de la profondeur des requêtes si activé
		$profondeur_limit = lire_config('/meta_graphql/securite/profondeur_limit', 0);
		if ($profondeur_limit > 0) {
			$rule = new QueryDepth((int) $profondeur_limit);
			DocumentValidator::addRule($rule);
		}

		// Désactivation du schéma si option cochée dans le BO
		if (in_array('oui', lire_config('/meta_graphql/securite/desactiver_schema', []))) {
			$rule = new DisableIntrospection(DisableIntrospection::ENABLED);
			DocumentValidator::addRule($rule);
		}

		// Exécution de la requête
		$output = GraphQL::executeQuery(
			$schema,
			$query,
			$rootValue,
			$contexte,
			$variableValues,
		);

		// On vérifie si le mode debug est activé
		if (in_array('true', lire_config('/meta_graphql/config/debug', []))) {
			$output = $output->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
		} else {
			$output = $output->toArray();
		}
	} catch (\Exception $e) {
		// Gestion des erreurs
		$output = [
			'errors' => [
				[
					'message' => $e->getMessage()
				]
			]
		];
	}

	// Envoi de la réponse
	header('Content-Type: application/json');
	echo json_encode(
		$output,
		JSON_THROW_ON_ERROR
	);
}
