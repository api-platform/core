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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6354;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ApiResource(
    graphQlOperations: [
        new Mutation(
            resolver: 'app.graphql.mutation_resolver.activity_log',
            name: 'create'
        ),
    ]
)]
class ActivityLog
{
    public function __construct(#[NotBlank()] public ?string $name = null)
    {
    }
}
