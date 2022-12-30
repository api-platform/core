<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Elasticsearch\Metadata;

use ApiPlatform\Metadata\PersistenceMeansInterface;

class ElasticsearchDocument implements PersistenceMeansInterface
{
    public function __construct(
        public readonly ?string $index = null,
        public readonly ?string $type = null,
    ) {
    }
}
