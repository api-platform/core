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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\BooleanQueryParameterDefault;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['hydra_prefix' => false],
            uriTemplate: '/boolean_query_parameter_defaults',
            parameters: [
                'booleanParameter' => new QueryParameter(
                    schema: [
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    castToNativeType: true,
                ),
                'anotherBooleanParameter' => new QueryParameter(
                    default: true,
                ),
            ],
            provider: [self::class, 'provide'],
        ),
    ]
)]
class BooleanQueryParameterDefault
{
    public function __construct(
        public bool $booleanParameter,
        public bool $anotherBooleanParameter,
    ) {
    }

    public static function provide(Operation $operation): self
    {
        return new self(
            $operation->getParameters()->get('booleanParameter')->getValue(),
            $operation->getParameters()->get('anotherBooleanParameter')->getValue(),
        );
    }
}
