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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ChoiceConstrainedScalarParameter;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/choice_constrained_scalar_parameters',
            parameters: [
                'days' => new QueryParameter(
                    schema: ['type' => 'integer', 'enum' => self::DAYS],
                    constraints: [new Assert\Choice(choices: self::DAYS)],
                    castToNativeType: true,
                ),
                'ratio' => new QueryParameter(
                    schema: ['type' => 'number', 'enum' => self::RATIOS],
                    constraints: [new Assert\Choice(choices: self::RATIOS)],
                    castToNativeType: true,
                ),
                'active' => new QueryParameter(
                    schema: ['type' => 'boolean'],
                    constraints: [new Assert\Choice(choices: [true, false])],
                    castToNativeType: true,
                ),
            ],
            provider: [self::class, 'provide'],
        ),
    ]
)]
class ChoiceConstrainedScalarParameter
{
    public const DAYS = [7, 30, 90];
    public const RATIOS = [0.5, 1.5];

    public static function provide(Operation $operation): array
    {
        return [];
    }
}
