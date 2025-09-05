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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7354;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['hydra_prefix' => false],
            uriTemplate: '/issue7354_boolean_query_parameters',
            parameters: [
                'booleanParameter' => new QueryParameter(
                    schema: [
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    castToNativeType: true,
                ),
            ],
            provider: [self::class, 'provide'],
        ),
    ]
)]
class BooleanQueryParameter
{
    public function __construct(public bool $booleanParameter)
    {
    }

    public static function provide(Operation $operation): self
    {
        return new self($operation->getParameters()->get('booleanParameter')->getValue());
    }
}
