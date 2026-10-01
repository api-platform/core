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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

/**
 * Resource whose discriminator map contains a subtype declaring its own (nested) discriminator map.
 */
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch(),
    ],
    // input and output share their definitions: the flag is set on both so the JSON Schema restricts additional properties
    normalizationContext: ['allow_extra_attributes' => false],
    denormalizationContext: ['allow_extra_attributes' => false],
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
#[DiscriminatorMap(typeProperty: 'kind', mapping: [
    'cat' => DiscriminatedPetCat::class,
    'dog' => DiscriminatedPetDog::class,
])]
abstract class DiscriminatedPet
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    public string $name = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self|array|null
    {
        if ($operation instanceof GetCollection) {
            return array_values(DiscriminatedPetStore::$pets);
        }

        return DiscriminatedPetStore::$pets[(int) $uriVariables['id']] ?? null;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        if (isset($uriVariables['id'])) {
            $data->id = (int) $uriVariables['id'];
        }

        $data->id ??= (DiscriminatedPetStore::$pets ? max(array_keys(DiscriminatedPetStore::$pets)) : 0) + 1;

        return DiscriminatedPetStore::$pets[$data->id] = $data;
    }
}
