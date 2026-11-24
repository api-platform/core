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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SharedBackingEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [new Get(), new Patch(), new GetCollection(), new Post()],
    mercure: true,
    stateOptions: new Options(entityClass: SharedBackingEntity::class),
)]
#[Map(target: SharedBackingEntity::class)]
final class SharedBackingThing
{
    public ?int $id = null;

    public string $name;

    public string $kind;
}
