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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    normalizationContext: ['hydra_prefix' => false],
    operations: [
        new Get(hideHydraOperation: true),
        new GetCollection(hideHydraOperation: true),
    ]
)]
final class HideHydraOperation
{
    public function __construct(public string $id, public string $title)
    {
    }
}
