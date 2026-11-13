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

namespace ApiPlatform\Serializer\Tests\Fixtures\Model;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Attribute\SerializedPath;

class HasSerializerAttributes
{
    #[ApiProperty(serialize: [new MaxDepth(2)])]
    public ?self $shallow = null;

    #[ApiProperty(serialize: [new MaxDepth(7)])]
    public ?self $deep = null;

    #[ApiProperty(serialize: [new SerializedName('renamed')])]
    public ?string $first = null;

    #[ApiProperty(serialize: [new SerializedName('otherName')])]
    public ?string $second = null;

    #[ApiProperty(serialize: [new SerializedPath('[nested][path]')])]
    public ?string $nested = null;

    #[ApiProperty(serialize: [new SerializedPath('[other][spot]')])]
    public ?string $elsewhere = null;

    #[ApiProperty(serialize: [new Groups(['read'])])]
    public ?string $readable = null;

    #[ApiProperty(serialize: [new Groups(['write', 'admin'])])]
    public ?string $writable = null;

    #[ApiProperty(serialize: [new Ignore()])]
    public ?string $hidden = null;

    public ?string $plain = null;

    #[ApiProperty(serialize: [new Context(normalizationContext: ['norm' => 'n'], denormalizationContext: ['denorm' => 'd'], groups: ['split'])])]
    public ?string $splitContext = null;

    #[ApiProperty(serialize: [new Context(context: ['shared' => 's'], groups: ['both'])])]
    public ?string $sharedContext = null;
}
