<?php

declare(strict_types=1);

use SpipLeague\EasyCodingStandard\Set\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSets([SetList::SPIP_LEAGUE])
    ->withPaths([__DIR__ . '/config', __DIR__ . '/src'])
    ->withRootFiles()
    ->withParallel()
;
