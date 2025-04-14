Exposer dynamiquement chaque collection, ainsi que ses champs, sélectionnés dans le navigateur.


- Le fichier `SchemaSPIP.php` gère toute la logique de construction du schéma
- Le fichier `ReponseSPIP.php` gère les fonctions utilisées dans les réponses.
- Le fichier `BufferSPIP.php` gère un buffer pour éviter les requêtes multiples ([résolution du problème N+1](https://webonyx.github.io/graphql-php/data-fetching/#solving-n1-problem)) : Si votre requête retourne 100 articles et que dans la même requête, pour chaque article, vous souhaitez récupérer la rubrique correspondante, le programme fera 1 + 100 requêtes pour récupérer chaque rubrique de chaque article. Pour éviter ça, on stocke les ids dans un buffer et requête tout avec un `SQL IN`. Il n'y a plus que 2 requêtes au lieu de 1 + N requêtes (en réalité ici, il y aura 3 requêtes au lieu de 2 + N car il y a une requête pour compter le nbre total de résultats).

## TODO
- [ ] Trouver une solution technique pour récupérer un objet par son slug
- [x] gérer les collections liées
- [x] Voir pour la requête concernant la recherche
- [x] créer un type de base pour tous les objets éditoriaux avec les champs en commun
- [x] Voir pour mettre en place le schéma en [lazy loading](https://webonyx.github.io/graphql-php/schema-definition/#lazy-loading-of-types) grâce à un `TypeRegistry`
