<?php

declare(strict_types=1);

namespace SpipLeague\Component\Rector\Set\SetProvider;

use Rector\Set\Contract\SetInterface;
use Rector\Set\Contract\SetProviderInterface;
use Rector\Set\ValueObject\Set;
use SpipLeague\Component\Rector\Set\Enum\SetGroup;

/**
 * @api collected in core
 */
final class SpipSetProvider implements SetProviderInterface
{
    /**
     * @return SetInterface[]
     */
    public function provide(): array
    {
        return [new Set(SetGroup::SPIP, 'SPIP 41', __DIR__ . '/../../../config/sets/spip-41.php')];
    }
}
