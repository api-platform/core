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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\JsonApi\Serializer\ItemNormalizer;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;

#[ApiResource(
    shortName: 'JsonApiPatchableDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Get(
            uriTemplate: '/jsonapi_patchable_dummies/{id}',
            provider: [self::class, 'provide'],
        ),
        new Patch(
            uriTemplate: '/jsonapi_patchable_dummies/{id}',
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
        ),
        new Patch(
            uriTemplate: '/jsonapi_strict_patchable_dummies/{id}',
            denormalizationContext: [ItemNormalizer::STRICT_RESOURCE_TYPE => true],
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
        ),
    ],
)]
class PatchableDummy
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public string $name = 'original';

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $resource = new self();
        $resource->id = (int) $uriVariables['id'];

        return $resource;
    }

    public static function process(self $data): self
    {
        return $data;
    }
}
