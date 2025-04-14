<?php

declare(strict_types=1);

namespace SPIP\GraphQL;

class ReponseSPIP {
	public static function findMeta(): array {
		$metas = lire_config("/meta_graphql/meta", []);
		$where = "nom IN ('" . implode("','", $metas) . "')";
		$liste_meta_publique = sql_allfetsel("nom, valeur", "spip_meta", $where);

		$retourMeta = [];
		if (!empty($liste_meta_publique)) {
			foreach ($liste_meta_publique as $meta) {
				$retourMeta[$meta["nom"]] = $meta["valeur"];
			}
		}

		return $retourMeta;
	}

	public static function recherche(string $texte, string $lang, int $pagination, int $page = 1): array {
		$retour = [];
		$collections_autorisees = lire_config('/meta_graphql/objets_editoriaux');

		foreach ($collections_autorisees as $collection => $config) {
			$result = self::searchCollection($collection, $texte, ['collection.lang=' . sql_quote($lang)]);

			$retour = array_merge(
				$retour,
				$result
			);
		}

		// Tri par points
		usort($retour, function ($item1, $item2) {
			return $item2['points'] <=> $item1['points'];
		});

		// Filtrage par apport à la pagination
		$totalPages = ceil(count($retour) / $pagination);
		$offset = ($page - 1) * $pagination;
		$retour = array_slice($retour, $offset, $pagination);

		return [
			'pagination' => [
				'currentPage' => $page,
				'totalPages' => $totalPages,
				'hasPreviousPage' => ($page > 1) ? true : false,
				'hasNextPage' => ($page < $totalPages) ? true : false,
			],
			'result' => $retour
		];
	}

	public static function afficheCollections(): array {
		$collections_autorisees = lire_config('/meta_graphql/objets_editoriaux');
		$reponse = [];

		foreach ($collections_autorisees as $collection => $config) {
			$reponse[] = strtoupper($collection);
		}
		return $reponse;
	}

	// Récupération d'une collection d'objets
	public static function findCollection(string $collection, array $where, int $pagination, int $page = 1): ?array {
		$retour_objets = [];
		$table = table_objet_sql($collection);
		$from = $table . ' AS collection';
		$where = self::getWhere($table, $where);
		$offset = ($page - 1) * $pagination;
		$limit = ($pagination == 0) ? '' : "$offset,$pagination";

		if ($limit != '') {
			$totalObjets =  sql_countsel($from, $where);
			$totalPages = ceil($totalObjets / $pagination);
		}

		$objets = sql_allfetsel(
			self::getSelect($table),
			$from,
			$where,
			'',
			'',
			$limit
		);

		foreach ($objets as $objet) {
			$retour_objets[] = self::findObjet((int) $objet['id'], $collection, $objet);
		}

		return ($limit == '') ? $retour_objets : [
			'pagination' => [
				'currentPage' => $page,
				'totalPages' => $totalPages,
				'hasPreviousPage' => ($page > 1) ? true : false,
				'hasNextPage' => ($page < $totalPages) ? true : false,
			],
			'result' => $retour_objets
		];
	}

	// Récupération d'un objet
	public static function findObjet(int $id, string $type_objet, array $objet = []): ?array {
		include_spip('inc/filtres');

		$collection = table_objet($type_objet);

		if (preg_match('#^get(\w+)$#', $collection, $matches)) {
			$collection = strtolower($matches[1]);
		}

		$type_objet = objet_type($collection);
		$champ_id = id_table_objet($collection);

		if (empty($objet)) {
			$table = table_objet_sql($collection);
			$where = [$champ_id . "=" . $id];
			$objet = sql_fetsel(
				self::getSelect($table),
				$table . ' AS collection',
				self::getWhere($table, $where)
			);
		}

		if (!$objet) return [];

		// Champ de base pour créer le champ slug
		$slug_origin = self::champSlug($collection);

		// Récupération des champs
		foreach ($objet as $champ => $value) {
			switch ($champ) {
				case "titre":
					$objet[$champ] = supprimer_numero($value);
					$objet['rang'] = (preg_match("#^([0-9]+)[.][[:space:]]#", $value, $matches)) ?
						$matches[1] : '0';
					break;
				case "fichier":
					$objet[$champ] = url_absolue(_DIR_IMG . $value);
					break;
				case "saisies":
					// TODO : transformer le tableau de saisies en html
					$objet[$champ] = $value;
					break;
				case "texte":
				case "surtitre":
				case "soustitre":
				case "descriptif":
				case "chapo":
				case "bio":
				case "credits":
					// Transformation des liens et des balises 
					$objet[$champ] = liens_absolus(trim(str_replace("\n", '<br>', propre($value))));
					break;
					// Gestion des laisons SQL 1 => N ascendantes (voir les resolvers dans SchemaSPIP.php)
				case "id_secteur":
				case "id_rubrique":
				case "id_groupe":
				case "id_trad":
				case "id_parent":
					break;
				default:
					$objet[$champ] = $value;
					break;
			}

			// Récupération du slug de l'objet
			if ($champ == $slug_origin) {
				$objet['slug'] = identifiant_slug(supprimer_numero($value));
			}
		}

		$objet['typeCollection'] = strtoupper($collection);

		// Récupération du logo de l'objet s'il existe
		include_spip('public/quete');

		if ($collection == 'documents' && $objet['id_vignette'] !== '0') {
			// Si on est sur un document
			include_spip('inc/documents');
			$objet['logo'] = lire_config("adresse_site") . "/" . vignette_logo_document($objet);
		} else {
			// Si on est sur un objet éditorial
			$logo =  quete_logo_objet($objet['id'], $type_objet, 'on');
			if (array_key_exists('chemin', $logo)) {
				$objet['logo'] = lire_config("adresse_site") . "/" . $logo['chemin'];
			}
		}

		// Récupération des collections liées (liaisons SQL N => N)
		$collections_autorisees = lire_config("/meta_graphql/objets_editoriaux");
		if (!array_key_exists($collection, $collections_autorisees)) return $objet;

		$config_collection = $collections_autorisees[table_objet($type_objet)];

		$liaisons_config = (array_key_exists('liaisons', $config_collection) &&
			is_array($config_collection['liaisons'])) ? $config_collection['liaisons'] : [];

		foreach ($liaisons_config as $collection_liee) {
			include_spip('action/editer_liens');
			$objet[$collection_liee] = [];
			$table_liee = table_objet_sql($collection_liee);
			$type_objet_lie = objet_type($table_liee);
			$cle_collection_liee = id_table_objet($table_liee);

			if (
				array_key_exists($collection_liee, $collections_autorisees)
				&& $liaison_col = objet_trouver_liens([$type_objet_lie => '*'], [$type_objet => $id])
			) {

				foreach ($liaison_col as $l) {
					$ids[] = $l[$cle_collection_liee];
				}

				$where = [sql_in($cle_collection_liee, $ids)];

				$objet[$collection_liee] = self::findCollection(
					$collection_liee,
					$where,
					(int) $collections_autorisees[$collection_liee]['pagination']
				);
			}
		}

		return $objet;
	}

	public static function searchCollection(string $collection, string $recherche, array $where = []): array {
		include_spip('inc/prepare_recherche');
		include_spip('inc/filtres');

		$retour_recherche = [];

		// Préparation et exécution de la requête
		$table = table_objet_sql($collection);
		$select = self::getSelect($table);
		$prepare_requete = inc_prepare_recherche_dist($recherche, $table);
		$select[] = $prepare_requete[0];
		$from = [$table . ' as collection', table_objet_sql("resultats") .  " as resultats",];
		$where[] = $prepare_requete[1];
		$where[] = 'collection.' . id_table_objet($table) . '=resultats.id';
		$result = sql_allfetsel($select, $from, $where, '', ['points DESC']);

		foreach ($result as $objet) {
			$retour_recherche[] = self::findObjet((int) $objet['id'], $collection, $objet);
		}

		return $retour_recherche;
	}

	public static function champSlug(string $collection) {
		switch ($collection) {
			case "auteurs":
				return "nom";
				break;
			case "syndic":
				return "nom_site";
				break;
			default:
				return "titre";
		}
	}

	public static function getSelect(string $table): array {
		$select = ['collection.' . id_table_objet($table) . ' as id', 'collection.maj'];

		// Détermination des champs à sélectionner par défaut en fonction de la collection
		switch ($table) {
			case 'spip_documents':
				$select[] = 'collection.titre';
				$select[] = 'collection.descriptif';
				$select[] = 'collection.id_vignette';
				break;
			case 'spip_auteurs':
				$select[] = 'collection.nom as titre';
				$select[] = 'collection.bio as descriptif';
				break;
			case 'spip_syndic':
				$select[] = 'collection.nom_site as titre';
				$select[] = 'collection.descriptif';
				break;
			default:
				$select[] = 'collection.titre';
				$select[] = 'collection.descriptif';
				break;
		}

		// Récupération des champs sélectionnés par le webmestre
		$champs_selectionnes =
			lire_config('/meta_graphql/objets_editoriaux/' . table_objet($table) . '/champs', []);

		foreach ($champs_selectionnes as $champ_selectionne) {
			$select[] = 'collection.' . $champ_selectionne;
		}

		return $select;
	}

	private static function getWhere(string $table, array $where = []): array {
		$table_infos = lister_tables_objets_sql($table);

		$retour_where = [];
		if (!empty($where)) {
			// Format des dates : 2023-05-19 11:00:00
			foreach ($where as $clause) {
				if (preg_match('#^(\w+)(=|<|>|<>)((?:\w+|\s|:|-)+)$#', $clause, $matches)) {
					$retour_where[] = 'collection.' . $matches[1] . $matches[2] . sql_quote($matches[3]);
				} else {
					$retour_where[] = $clause;
				}
			}
		}

		if ($table != "spip_auteurs" && array_key_exists("statut", $table_infos["field"])) {
			$retour_where[] = "collection.statut=" . sql_quote('publie');
		}

		return $retour_where;
	}
}
