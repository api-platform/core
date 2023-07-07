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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource]
#[GetCollection(output: DtoInterface::class, provider: [self::class, 'provide'])]
class EntityWithDtoOutput
{
    private string $name;

    private string $city;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return [
            new DtoOutput('Sarah'),
        ];
    }
}

interface DtoInterface
{
    public function getName(): string;
}

class DtoOutput implements DtoInterface
{
    public function __construct(private readonly string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
