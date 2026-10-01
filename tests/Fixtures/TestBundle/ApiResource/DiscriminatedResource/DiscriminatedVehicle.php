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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Discriminated resource whose subtypes override inherited properties (narrowed types, metadata, constraints).
 */
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ],
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
#[DiscriminatorMap(typeProperty: 'kind', mapping: [
    'car' => DiscriminatedCar::class,
    'truck' => DiscriminatedTruck::class,
])]
abstract class DiscriminatedVehicle
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    #[Assert\NotBlank]
    public string $name = '';

    public ?string $plate = null;

    public ?DiscriminatedVehicle $tows = null;

    protected ?DiscriminatedVehicle $trailer = null;

    public function getTrailer(): ?self
    {
        return $this->trailer;
    }

    public function setTrailer(?self $trailer): void
    {
        $this->trailer = $trailer;
    }

    public function getRating(): int|float
    {
        return 4.5;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self|array|null
    {
        if ($operation instanceof GetCollection) {
            return array_values(DiscriminatedVehicleStore::$vehicles);
        }

        return DiscriminatedVehicleStore::$vehicles[(int) $uriVariables['id']] ?? null;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        if ($operation instanceof Delete) {
            unset(DiscriminatedVehicleStore::$vehicles[(int) $uriVariables['id']]);

            return null;
        }

        if (isset($uriVariables['id'])) {
            $data->id = (int) $uriVariables['id'];
        }

        $data->id ??= (DiscriminatedVehicleStore::$vehicles ? max(array_keys(DiscriminatedVehicleStore::$vehicles)) : 0) + 1;

        return DiscriminatedVehicleStore::$vehicles[$data->id] = $data;
    }
}
