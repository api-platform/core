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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GenderTypeEnum;

#[ApiResource()]
#[Get(
    provider: self::class.'::providerItem',
)]
#[GetCollection(
    provider: self::class.'::providerCollection',
)]
class ResourceWithEnumProperty
{
    public int $id = 1;

    #[ApiProperty(readableLink: true)]
    public ?BackedEnumIntegerResource $intEnum = null;

    /** @var BackedEnumStringResource[] */
    public array $stringEnum = [];

    public ?GenderTypeEnum $gender = null;

    /** @var GenderTypeEnum[] */
    public array $genders = [];

    public static function providerItem(Operation $operation, array $uriVariables): self
    {
        $self = new self();
        $self->intEnum = BackedEnumIntegerResource::Yes;
        $self->stringEnum = [BackedEnumStringResource::Maybe, BackedEnumStringResource::No];
        $self->gender = GenderTypeEnum::FEMALE;
        $self->genders = [GenderTypeEnum::FEMALE, GenderTypeEnum::MALE];

        return $self;
    }

    public static function providerCollection(Operation $operation, array $uriVariables): array
    {
        return [self::providerItem($operation, $uriVariables)];
    }
}
