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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ExactFilterSchemaParameter;

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;

#[GetCollection(
    uriTemplate: '/exact_filter_schema_parameters',
    normalizationContext: ['hydra_prefix' => false],
    parameters: [
        // Scalar schema, default castToArray (null) -> both singular and array variants.
        'code' => new QueryParameter(
            filter: new ExactFilter(),
            property: 'code',
            schema: ['type' => 'integer'],
        ),
        // Scalar schema, castToArray: true -> array variant only.
        'codeArrayCast' => new QueryParameter(
            filter: new ExactFilter(),
            property: 'code',
            schema: ['type' => 'integer'],
            castToArray: true,
        ),
        // Scalar schema, castToArray: false -> singular variant only.
        'codeNoArray' => new QueryParameter(
            filter: new ExactFilter(),
            property: 'code',
            schema: ['type' => 'integer'],
            castToArray: false,
        ),
        // Already an array schema, castToArray: true -> single array variant, no duplicate.
        'codeExplicitArray' => new QueryParameter(
            filter: new ExactFilter(),
            property: 'code',
            schema: ['type' => 'array', 'items' => ['type' => 'integer']],
            castToArray: true,
        ),
    ],
    provider: [self::class, 'provide'],
)]
class ExactFilterSchemaParameter
{
    public function __construct(
        public int $code = 0,
    ) {
    }

    /**
     * @return self[]
     */
    public static function provide(Operation $operation): array
    {
        return [];
    }
}
