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

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SharedBackingEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new Get(),
        new Patch(),
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/shared_backing_owners/{ownerId}/gadgets',
            uriVariables: ['ownerId' => new Link(fromClass: self::class, identifiers: ['ownerId'])],
        ),
        new GetCollection(
            uriTemplate: '/shared_backing_kinds/{kind}/gadgets',
            uriVariables: ['kind' => new Link(fromClass: self::class, identifiers: ['kind'])],
        ),
    ],
    stateOptions: new Options(entityClass: SharedBackingEntity::class),
)]
#[Map(target: SharedBackingEntity::class)]
final class SharedBackingGadget
{
    public ?int $id = null;

    public string $name;

    public string $kind;
}
