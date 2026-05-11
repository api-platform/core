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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    shortName: 'JsonLdInterfaceDtoOutput',
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_interface_dto_outputs',
            output: InterfaceDtoOutputDto::class,
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class InterfaceDtoOutputResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $name = '';

    public string $city = '';

    public static function provide(): array
    {
        return [new InterfaceDtoOutputImpl(1, 'Sarah')];
    }
}

interface InterfaceDtoOutputDto
{
    public function getName(): string;
}

final class InterfaceDtoOutputImpl implements InterfaceDtoOutputDto
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly int $id,
        private readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
