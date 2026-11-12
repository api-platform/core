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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\IriDiscriminatedResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[ApiResource(
    operations: [new Get(), new Post()],
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
#[DiscriminatorMap(typeProperty: 'kind', mapping: ['car' => IriDiscriminatedCar::class])]
abstract class IriDiscriminatedVehicle
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    public string $name = '';

    public ?IriDiscriminatedVehicle $tows = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        return IriDiscriminatedVehicleStore::$vehicles[(int) $uriVariables['id']] ?? null;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $data->id ??= (IriDiscriminatedVehicleStore::$vehicles ? max(array_keys(IriDiscriminatedVehicleStore::$vehicles)) : 0) + 1;

        return IriDiscriminatedVehicleStore::$vehicles[$data->id] = $data;
    }
}
