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

use ApiPlatform\Doctrine\Orm\State\Options as OrmOptions;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\CreateUniqueBookDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UniqueBook;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new Post(input: CreateUniqueBookDto::class),
        new Get(),
        new GetCollection(),
    ],
    stateOptions: new OrmOptions(entityClass: UniqueBook::class)
)]
#[Map(source: UniqueBook::class)]
class UniqueBookResource
{
    public int $id;
    public string $isbn = '';
    public string $title = '';
}
