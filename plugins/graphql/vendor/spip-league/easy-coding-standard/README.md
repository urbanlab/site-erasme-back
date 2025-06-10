# EasyCodingStandard configuration for Spip

Default configuration for [SPIP](https://www.spip.net) of [EasyCodingStandard](https://github.com/Symplify/EasyCodingStandard) 

## Installation

```bash
composer require spip-league/easy-coding-standard --dev
```

## Usage

Create a file named `ecs.php` in the root directory.

For a plugin SPIP, you can use `SetList::SPIP`
which use an adapted (indentation with tabulation!) PER 2.0 style 

```php
<?php

declare(strict_types=1);

use SpipLeague\EasyCodingStandard\Set\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSets([SetList::SPIP])
    // Adapt 
    ->withPaths([__DIR__])
    ->withSkip([
        __DIR__ . '/lang',
		__DIR__ . '/vendor',
    ])
;
```

For a library in spip-league, you can use `SetList::SPIP_LEAGUE`
which use a more traditionnal PER 2.0 style

```php
<?php

declare(strict_types=1);

use SpipLeague\EasyCodingStandard\Set\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSets([SetList::SPIP_LEAGUE])
    // Adapt 
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
;
```

Then run ecs script

```bash
vendor/bin/ecs check
vendor/bin/ecs --fix
```
