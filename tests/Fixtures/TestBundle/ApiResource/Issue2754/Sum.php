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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue2754;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    graphQlOperations: [
        new Query(
            name: 'item_query',
            provider: [self::class, 'provide'],
        ),
        new Mutation(
            name: 'sum',
            resolver: 'app.graphql.mutation_resolver.issue2754_sum',
            output: SumResult::class,
            args: ['operandA' => ['type' => 'Int!'], 'operandB' => ['type' => 'Int!']],
        ),
    ]
)]
class Sum
{
    public function __construct(public ?int $id = null, public ?int $operandA = null, public ?int $operandB = null)
    {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return new self(1);
    }
}
