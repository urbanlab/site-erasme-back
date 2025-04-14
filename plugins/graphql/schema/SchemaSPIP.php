<?php

declare(strict_types=1);

namespace SPIP\GraphQL;

use GraphQL\Deferred;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;


// Permet de créer des types ré-utilisables dans l'API
// https://webonyx.github.io/graphql-php/schema-definition/#lazy-loading-of-types
class SchemaSPIP {
    public array $collections_autorisees = [];
    public array $metas_autorisees = [];

    /**
     * @var array<ObjectType, InterfaceType, EnumType>
     */
    private array $types = [];

    public function __construct() {
        $this->collections_autorisees = lire_config('/meta_graphql/objets_editoriaux', []);
        $this->metas_autorisees = lire_config('/meta_graphql/meta', []);
    }

    // Query, Date, MetaList, Article, SearchResult...
    public function get(string $name) {
        return $this->types[$name] ??= $this->getType($name);
    }

    // Pour créer un nouveau Type de données, c'est par ici
    private function getType(string $name) {
        $typeDefinition = ['name' => $name];

        // Types personnalisés (permet de surcharger les types du plugin et de créer ses propres types)
        $dir_types = find_in_path('action/graphql/types');
        $files = scandir($dir_types);
        foreach ($files as $filename) {
            if ($filename == $name . '.php') {
                return new ObjectType(array_merge($typeDefinition, include $dir_types . '/' . $filename));
            }
        }

        // Types proposés par le plugin
        switch ($name) {
            case 'Collection':
                // Liste des collections exposées
                $typeDefinition['description'] = _T('graphql:desc_type_collection');

                foreach ($this->collections_autorisees as $collection => $config) {
                    $typeDefinition['values'][] = strtoupper($collection);
                }

                return new EnumType($typeDefinition);
                break;
            case 'Date':
                // Type scalaire personnalisé
                // https://webonyx.github.io/graphql-php/type-definitions/scalars/#writing-custom-scalar-types
                // AAAA-MM-JJ HH:MM:SS
                $typeDefinition['description'] = _T('graphql:desc_type_date');
                // $typeDefinition['serialize']
                // $typeDefinition['parseValue']
                // $typeDefinition['parseLiteral']
                return new CustomScalarType($typeDefinition);
                break;
            case 'MetaList':
                // Liste des mets exposées
                $typeDefinition['description'] = _T('graphql:desc_type_metalist');
                $typeDefinition['fields'] = function () {
                    $champs_schema = [];
                    foreach ($this->metas_autorisees as $meta) {
                        $champs_schema[$meta] = Type::string();
                    }
                    return $champs_schema;
                };
                return new ObjectType($typeDefinition);
                break;
            case 'ObjetPagination':
                // Interface ObjetPagination pour mutualiser des champs
                $typeDefinition['description'] = _T('graphql:desc_type_collection_pagination');

                $typeDefinition['fields'] = function () {
                    return [
                        'pagination' => $this->get('Pagination'),
                        'result' => new ListOfType($this->get('Objet'))
                    ];
                };

                $typeDefinition['resolveType'] = function ($value, $context, ResolveInfo $info) {
                    // TODO : écrire le resolveType pour les requêtes qui retourneraient un Objet ou un tableau d'Objet
                    // switch ($info->fieldDefinition->name ?? null) {
                    //     case 'human': return MyTypes::human();
                    //     case 'droid': return MyTypes::droid();
                    //     default: throw new Exception("Unknown Character type: {$value->type ?? null}");
                    // }
                };
                return new InterfaceType($typeDefinition);
                break;
            case 'Objet':
                // Interface Objet pour mutualiser des champs entre objets
                $typeDefinition['description'] = _T('graphql:desc_type_objet');

                $typeDefinition['fields'] = function () {
                    $champs_interface = [];
                    foreach (GRAPHQL_CHAMPS_COMMUNS as $champ) {
                        switch ($champ) {
                            case 'id':
                                $champs_interface[$champ] = Type::id();
                                break;
                            case 'maj':
                                $champs_interface[$champ] = $this->get('Date');
                                break;
                            case 'typeCollection':
                                $champs_interface[$champ] = $this->get('Collection');
                                break;
                            case 'rang':
                            case 'points':
                                $champs_interface[$champ] = Type::int();
                                break;
                            default:
                                $champs_interface[$champ] = Type::string();
                                break;
                        }
                    }

                    return $champs_interface;
                };

                $typeDefinition['resolveType'] = function ($value, $context, ResolveInfo $info) {
                    // TODO : écrire le resolveType pour les requêtes qui retourneraient un Objet ou un tableau d'Objet
                    // switch ($info->fieldDefinition->name ?? null) {
                    //     case 'human': return MyTypes::human();
                    //     case 'droid': return MyTypes::droid();
                    //     default: throw new Exception("Unknown Character type: {$value->type ?? null}");
                    // }
                };
                return new InterfaceType($typeDefinition);
                break;

            case 'Pagination':
                // Pour gérer la pagination
                $typeDefinition['description'] = _T('graphql:desc_type_pagination');
                $typeDefinition['fields'] = function () {
                    return [
                        'currentPage' => new NonNull(Type::int()),
                        'totalPages' => new NonNull(Type::int()),
                        'hasPreviousPage' => new NonNull(Type::boolean()),
                        'hasNextPage' => new NonNull(Type::boolean())
                    ];
                };
                return new ObjectType($typeDefinition);
                break;
            case 'Query':
                // Les requêtes disponibles
                $typeDefinition['description'] = _T('graphql:desc_type_query');
                $typeDefinition['fields'] = fn () => $this->getQueryFields();
                return new ObjectType($typeDefinition);
                break;
            case 'RecherchePagination':
                // Type permettant de retourner les résultats de la recherche avec sa pagination
                $typeDefinition['description'] = _T('graphql:desc_type_recherchePagination');
                $typeDefinition['fields'] = function () {
                    return [
                        'pagination' => $this->get('Pagination'),
                        'result' => new ListOfType($this->get('SearchResult'))
                    ];
                };

                return new ObjectType($typeDefinition);
                break;
            case 'SearchResult':
                // Type UNION permettant de retourner tous les Types MonObjet (avec le champ 'points')
                // Dans la requête de recherche
                $typeDefinition['description'] = _T('graphql:desc_type_searchresult');

                foreach ($this->collections_autorisees as $collection => $config) {
                    $typeDefinition['types'][] = $this->get(ucfirst(objet_type($collection)));
                }

                $typeDefinition['resolveType'] = function ($value, $context, ResolveInfo $info) {
                    $collection = strtolower($value['typeCollection']);
                    $type_objet = objet_type($collection);
                    return $this->get(ucfirst($type_objet));
                    // throw new Exception("Unknown Object type: {$value['typeCollection'] ?? null}");
                };

                return new UnionType($typeDefinition);
                break;
        }

        // Type représentant la pagination d'une collection (ex : ArticlePagination)
        if (preg_match('#^([A-Z]{1}[[:alpha:]|_]{2,})Pagination$#', $name, $matches)) {
            $typeDefinition['description'] = _T('graphql:desc_type_collection_pagination');
            $typeDefinition['interfaces'] = [$this->get('ObjetPagination')];
            $typeDefinition['fields'] = function () use ($matches) {
                return [
                    'pagination' => $this->get('Pagination'),
                    'result' => new ListOfType($this->get($matches[1]))
                ];
            };
            return new ObjectType($typeDefinition);
        }

        // Type représentant une collection (ex : Article)
        if (preg_match('#^([A-Z]{1}[[:alpha:]|_]{2,})$#', $name, $matches)) {
            $collection = table_objet(strtolower($matches[1]));

            $typeDefinition['description'] = _T('graphql:desc_type_objet') . ' ' . $name;
            $typeDefinition['interfaces'] = [$this->get('Objet')];
            $typeDefinition['fields'] = function () use (&$collectionType, $collection): array {
                // On récupère les champs partagés
                $graphQLfields = [];
                foreach (GRAPHQL_CHAMPS_COMMUNS as $champ_commun) {
                    $graphQLfields[] = $this->get('Objet')->getField($champ_commun);
                }

                // On récupère les champs autorisés de la collection
                // Et on récupère le type SQL pour en déduire le type GraphQL
                $collection_infos = graphql_getCollectionInfos($collection);
                $info_champs = $collection_infos['champs'];
                foreach ($this->collections_autorisees[$collection]['champs'] as $champ) {
                    $fieldConfiguration = null;
                    $typeSQL = $info_champs[$champ] ? $info_champs[$champ] : "";

                    switch (true) {
                            // On gère d'abord les types de champs spécifiques qui retourneront un objet
                            // (Liaisons SQL 1 => N ascendantes)
                        case (in_array($champ, ['id_rubrique', 'id_secteur'])):
                            if (array_key_exists('rubriques', $this->collections_autorisees))
                                $fieldConfiguration = [
                                    'type' => $this->get('Rubrique'),
                                    'resolve' => function (array $currentObject, array $args, array $context, ResolveInfo $info) use ($champ): Deferred {
                                        BufferSPIP::add($currentObject[$champ], 'rubriques');

                                        return new Deferred(function () use ($currentObject, $champ) {
                                            return (($currentObject['typeCollection'] == 'RUBRIQUES') &&
                                                $currentObject['id'] == $currentObject[$champ]
                                            ) ? null : BufferSPIP::get($currentObject[$champ], 'rubriques');
                                        });
                                    }
                                ];
                            break;
                        case ($champ == 'id_groupe'):
                            if (array_key_exists('groupes_mots', $this->collections_autorisees))
                                $fieldConfiguration = [
                                    'type' => $this->get('Groupe_mots'),
                                    'resolve' => function (array $currentObject, array $args, array $context, ResolveInfo $info) use ($champ): Deferred {
                                        BufferSPIP::add($currentObject[$champ], 'groupes_mots');

                                        return new Deferred(function () use ($currentObject, $champ) {
                                            return ($currentObject[$champ] === 0) ? null : BufferSPIP::get($currentObject[$champ], 'groupes_mots');
                                        });
                                    }
                                ];
                            break;
                        case (in_array($champ, ['id_trad', 'id_parent'])):
                            $fieldConfiguration = [
                                'type' => $collectionType,
                                'resolve' => function (array $currentObject, array $args, array $context, ResolveInfo $info) use ($champ): Deferred {
                                    BufferSPIP::add($currentObject[$champ], strtolower($currentObject['typeCollection']));

                                    return new Deferred(function () use ($currentObject, $champ) {
                                        return (($currentObject[$champ] === '0') ||
                                            $currentObject['id'] === $currentObject[$champ]
                                        ) ? null : BufferSPIP::get($currentObject[$champ], strtolower($currentObject['typeCollection']));
                                    });
                                }
                            ];
                            break;
                            // Ensuite, on gère les types scalaires
                        case (stripos($typeSQL, 'double') !== false):
                            $fieldConfiguration = Type::float();
                            break;
                        case (stripos($typeSQL, 'smallint') !== false):
                        case (stripos($typeSQL, 'integer') !== false):
                        case (stripos($typeSQL, 'bigint') !== false):
                            $fieldConfiguration = Type::int();
                            break;
                        case (stripos($typeSQL, 'timestamp') !== false):
                        case (stripos($typeSQL, 'datetime') !== false):
                            $fieldConfiguration = $this->get('Date');
                            break;
                        default:
                            $fieldConfiguration = Type::string();
                            break;
                    }

                    if (!is_null($fieldConfiguration)) {
                        $champ = preg_replace('#(id_)#', '', $champ);
                        $graphQLfields[$champ] = $fieldConfiguration;
                    }
                }

                // Collections liées 1 => N descendantes
                foreach ($this->collections_autorisees as $collection_liee => $config) {
                    $infos_collection_liee = lister_tables_objets_sql(table_objet_sql($collection_liee));

                    // Le champ 'parent' doit être déclaré dans les infos de la table SQL
                    if (!isset($infos_collection_liee['parent'])) continue;

                    // Le type mot ne renvoit pas une liste mais un tableau associatif
                    $parent = (array_values($infos_collection_liee['parent']) !== $infos_collection_liee['parent']) ?
                        $infos_collection_liee['parent'] :
                        $infos_collection_liee['parent'][0];

                    // S'il ne s'agit pas d'un champ autorisé dans la collection liée
                    // Il faut par ex autoriser id_rubrique dans la collection Articles pour qu'un objet Rubrique puisse exposer ses Articles
                    if (!in_array($parent['champ'], $this->collections_autorisees[$collection_liee]['champs'])) continue;

                    // Si le champ correspond à la clé primaire de la table en cours
                    if (
                        $parent['champ'] ==  id_table_objet($collection) ||
                        ($parent['champ'] == 'id_parent' && $parent['type'] == objet_type($collection))
                    ) {
                        $graphQLfields[$collection_liee] = [
                            'type' => $this->get(ucfirst(objet_type($collection_liee)) . 'Pagination'),
                            'args' => $this->collectionArgs((int) $this->collections_autorisees[$collection_liee]['pagination']),
                            'resolve' => function ($rootValue, array $args, array $context, ResolveInfo $info) use ($parent) {
                                $type_enfant = objet_type($info->fieldDefinition->name);

                                $where = array_merge($args['where'], [$parent['champ'] . '=' . $rootValue['id']]);

                                return ReponseSPIP::findCollection(
                                    $type_enfant,
                                    $where,
                                    $args['pagination'],
                                    $args['page']
                                );
                            }
                        ];
                    }
                }

                $config_collection = $this->collections_autorisees[$collection];

                $liaisons_config = (array_key_exists('liaisons', $config_collection) &&
                    is_array($config_collection['liaisons'])) ? $config_collection['liaisons'] : [];

                foreach ($liaisons_config as $collection_liee) {
                    $table_liee = table_objet_sql($collection_liee);
                    $type_objet_lie = objet_type($table_liee);

                    if (array_key_exists($collection_liee, $this->collections_autorisees)) {
                        $graphQLfields[$collection_liee] = [
                            'type' => $this->get(ucfirst($type_objet_lie) . 'Pagination'),
                            'args' => $this->collectionArgs((int) $this->collections_autorisees[$collection]['pagination']),
                            'resolve' => function ($rootValue, array $args, array $context, ResolveInfo $info) {
                                include_spip('action/editer_liens');
                                $type_enfant = objet_type($info->fieldDefinition->name);
                                $primary_enfant = id_table_objet($type_enfant);
                                $collection_parent = strtolower($rootValue['typeCollection']);
                                $type_parent = objet_type($collection_parent);
                                $id_parent = $rootValue['id'];
                                $liaison_col = objet_trouver_liens([$type_enfant => '*'], [$type_parent => $id_parent]);

                                foreach ($liaison_col as $l) {
                                    $ids[] = $l[$primary_enfant];
                                }

                                $where = array_merge($args['where'], [sql_in($primary_enfant, $ids)]);

                                return ReponseSPIP::findCollection(
                                    $type_enfant,
                                    $where,
                                    $args['pagination'],
                                    $args['page']
                                );
                            }
                        ];
                    }
                }

                return $graphQLfields;
            };

            $collectionType = new ObjectType($typeDefinition);

            return $collectionType;
        }
    }

    // Pour créer une nouvelle requête, c'est par ici
    private function getQueryFields(): array {
        $queryFields = [];

        // requête pour les metas
        if (!empty($this->metas_autorisees)) {
            $queryFields['getMetas'] = [
                'type' => new NonNull($this->get('MetaList')),
                'description' => _T('graphql:desc_query_getmeta'),
                'resolve' => function ($rootValue, array $args, array $context, ResolveInfo $info) {
                    return ReponseSPIP::findMeta();
                }
            ];
        }

        if (!empty($this->collections_autorisees)) {
            // Requête pour récupérer la liste des collections
            $queryFields['getCollections'] = [
                'type' => new NonNull(new ListOfType($this->get('Collection'))),
                'description' => _T('graphql:desc_query_collections'),
                'resolve' => function ($rootValue, array $args, array $context, ResolveInfo $info) {
                    return ReponseSPIP::afficheCollections();
                }
            ];

            // Requête pour la recherche
            $queryFields['recherche'] = [
                'type' => new NonNull($this->get('RecherchePagination')),
                'description' => _T('graphql:desc_query_recherche'),
                'args' => [
                    'texte' => [
                        'type' => new NonNull(Type::string()),
                        'description' => _T('graphql:desc_arg_texte'),
                        'defaultValue' => ''
                    ],
                    'lang' =>
                    [
                        'type' => new NonNull(Type::string()),
                        'description' => _T('graphql:desc_arg_lang'),
                        'defaultValue' => 'fr'
                    ],
                    'pagination' => [
                        'type' => new NonNull(Type::int()),
                        'description' => _T('graphql:desc_arg_pagination'),
                        'defaultValue' => (int) lire_config('/meta_graphql/config/recherche_pagination', 10)
                    ],
                    'page' => [
                        'type' => new NonNull(Type::int()),
                        'description' => _T('graphql:desc_arg_page'),
                        'defaultValue' => 1
                    ]
                ],
                'resolve' => function ($rootValue, array $args, array $context, ResolveInfo $info) {
                    return ReponseSPIP::recherche($args['texte'], $args['lang'], $args['pagination'], $args['page']);
                }
            ];

            // Pour chaque collection exposée
            foreach ($this->collections_autorisees as $collection => $config) {
                $collection_infos = graphql_getCollectionInfos($collection);

                // Requête pour la collection
                $queryFields[$collection] = $this->getQueryCollection($collection_infos);

                // Requête pour un objet de la collection
                $queryFields['get' . $collection_infos['nameObjet']] = [
                    'type' => $this->get($collection_infos['nameObjet']),
                    'description' => _T('graphql:desc_query_objet') . ' ' . $collection_infos['nameObjet'],
                    'args' => [
                        'id' => [
                            'type' => new NonNull(Type::int()),
                            'description' => _T('graphql:desc_arg_id'),
                        ]
                    ],
                    'resolve' => function ($rootValue, array $args, array $context, ResolveInfo $info) {
                        return ReponseSPIP::findObjet((int) $args['id'], $info->fieldDefinition->name);
                    }
                ];
            }
        }

        // Inclusion des requêtes personnalisées (permet de surcharger les requêtes du plugin)
        $dir_requetes = find_in_path('action/graphql/requetes');
        $files = scandir($dir_requetes);
        foreach ($files as $filename) {
            $fileNameParts = explode('.', $filename);
            if (!in_array($filename, ['.', '..']) && end($fileNameParts) == 'php') {
                $queryFields[substr($filename, 0, -4)] = include $dir_requetes . '/' . $filename;
            }
        }

        return $queryFields;
    }

    private function getQueryCollection(array $collection_infos) {
        return [
            'type' => $this->get(ucfirst($collection_infos['type_objet']) . 'Pagination'),
            'description' => _T('graphql:desc_query_collection') . ' ' . $collection_infos['nameObjet'],
            'args' => $this->collectionArgs((int) $this->collections_autorisees[$collection_infos['collection']]['pagination']),
            'resolve' => function ($rootValue, array $args, array $context, ResolveInfo $info) {
                return ReponseSPIP::findCollection(
                    $info->fieldDefinition->name,
                    $args['where'],
                    $args['pagination'],
                    $args['page']
                );
            }
        ];
    }

    private function collectionArgs(int $pagination) {
        return [
            'where' => [
                'type' => new NonNull(new ListOfType(new NonNull(Type::string()))),
                'description' => _T('graphql:desc_arg_where'),
                'defaultValue' => []
            ],
            'pagination' => [
                'type' => new NonNull(Type::int()),
                'description' => _T('graphql:desc_arg_pagination'),
                'defaultValue' => $pagination
            ],
            'page' => [
                'type' => new NonNull(Type::int()),
                'description' => _T('graphql:desc_arg_page'),
                'defaultValue' => 1
            ]
        ];
    }
}
