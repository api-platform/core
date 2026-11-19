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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;

/**
 * Reproduces issue #8285: an explicit mutation description must not be
 * overwritten by the generated default.
 */
#[ApiResource(graphQlOperations: [
    new Mutation(name: 'create', description: 'My custom description.'),
    new Mutation(name: 'update'),
])]
final class MutationDescription
{
}
