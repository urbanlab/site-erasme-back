# Extension Rector pour SPIP

Permet d’effectuer certaines migrations de code pour les plugins SPIP.

## Installation

### Créer un composer.json s’il n’existe pas

```
composer init
composer install
```

### Déclarer le repository composer spip

```
composer config repositories.spip composer https://get.spip.net/composer
composer install
```


### Installer temporairement Rector.php dans les outils de dev

```
composer req --dev rector/rector ^2.0
composer req --dev spip-league/rector dev-main
```

### Déclarer la config rector.pph

Il créera le fichier rector.php la première fois.
```
vendor/bin/rector
```

### Déclarer sa config

Adapter `Paths` selon le projet.

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use SpipLeague\Component\Rector\Set\SpipSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/action',
        __DIR__ . '/base',
        __DIR__ . '/formulaires',
        __DIR__ . '/genie',
        __DIR__ . '/inc',
    ])
    ->withSets(
        [SpipSetList::SPIP_41]
    );

```