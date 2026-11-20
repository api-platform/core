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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Book;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * No embedded relation: nothing should be joined.
 */
#[ApiResource(
    operations: [new GetCollection(uriTemplate: '/issue8765_book_titles', paginationItemsPerPage: 6)],
    stateOptions: new Options(entityClass: Issue8765Book::class),
)]
#[Map(source: Issue8765Book::class)]
class Issue8765BookTitle
{
    public int $id;

    public string $title;
}
