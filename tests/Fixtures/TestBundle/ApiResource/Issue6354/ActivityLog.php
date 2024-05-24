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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ApiResource(
    operations: [
        new Get(
            provider: [self::class, 'provide']
        ),
    ],
    graphQlOperations: [
        new Mutation(
            resolver: 'app.graphql.mutation_resolver.activity_log',
            name: 'create'
        ),
        new DeleteMutation(
            name: 'delete'
        ),
    ]
)]
class ActivityLog
{
    public function __construct(public ?int $id = null, #[NotBlank()] public ?string $name = null)
    {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return new self(1);
    }
}
