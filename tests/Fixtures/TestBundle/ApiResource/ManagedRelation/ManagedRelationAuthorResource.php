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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ManagedRelation;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ManagedRelation\ManagedRelationAuthor;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new Get(),
        new Post(),
    ],
    shortName: 'ManagedRelationAuthor',
    stateOptions: new Options(entityClass: ManagedRelationAuthor::class)
)]
#[Map(source: ManagedRelationAuthor::class)]
class ManagedRelationAuthorResource
{
    public ?int $id = null;

    public string $name = '';
}
