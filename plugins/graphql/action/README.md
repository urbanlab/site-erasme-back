# Dossier d'entrée de l'API

## Fichier graphql.php
C'est ce fichier qui est appelé via l'url `spip.php?action=graphql` et qui est donc le point d'entrée de l'API

## Dossier `graphql`
Ce dossier contient 2 sous-dossiers permettant à un développeur de créer ses propres types ou requêtes.

Pour créer un nouveau type type ou requête à représenter dans le schéma de l'API, il suffit de créer un fichier portant le nom de votre type ou de votre requête dans le dossier adéquat. Chaque fichier retourne un tableau qui sera incorporéré dans le schéma d'introspection. Il y a 2 fichiers d'exemple que vous pouvez décommenter pour tester. Il est tout à fait possible d'utiliser les types fournis par le plugin. Par ex avec : `$this->get('ArticlePagination')` ou `$this->get('Rubrique')`. **Il faut surtout que la réponse de la requête soit conforme au type.** Sinon `graphql-php` renvoit `null`.

Pour ne perdre ses types et requêtes personnalisées lors d'une MAJ du plugin, le développeur peut créer les dossiers `/action/graphql/types` et `/action/graphql/requetes` à la racine