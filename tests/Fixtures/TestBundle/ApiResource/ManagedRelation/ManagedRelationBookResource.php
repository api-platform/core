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

use ApiPlatform\Doctrine\Orm\State\ManagedEntityTransform;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ManagedRelation\ManagedRelationBook;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new Get(),
        new Post(),
    ],
    shortName: 'ManagedRelationBook',
    stateOptions: new Options(entityClass: ManagedRelationBook::class)
)]
#[Map(source: ManagedRelationBook::class)]
class ManagedRelationBookResource
{
    public ?int $id = null;

    public string $title = '';

    #[Map(target: 'author', transform: ManagedEntityTransform::class)]
    public ?ManagedRelationAuthorResource $author = null;
}
