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
 * Concrete resource declaring itself in its own discriminator map.
 */
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch(),
    ],
    normalizationContext: ['allow_extra_attributes' => false],
    denormalizationContext: ['allow_extra_attributes' => false],
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
#[DiscriminatorMap(typeProperty: 'kind', mapping: [
    'gadget' => DiscriminatedGadget::class,
    'phone' => DiscriminatedPhone::class,
])]
class DiscriminatedGadget
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    public string $name = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self|array|null
    {
        if ($operation instanceof GetCollection) {
            return array_values(DiscriminatedGadgetStore::$gadgets);
        }

        return DiscriminatedGadgetStore::$gadgets[(int) $uriVariables['id']] ?? null;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        if (isset($uriVariables['id'])) {
            $data->id = (int) $uriVariables['id'];
        }

        $data->id ??= (DiscriminatedGadgetStore::$gadgets ? max(array_keys(DiscriminatedGadgetStore::$gadgets)) : 0) + 1;

        return DiscriminatedGadgetStore::$gadgets[$data->id] = $data;
    }
}
