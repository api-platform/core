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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8085;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new GetCollection(
            paginationItemsPerPage: 3,
            paginationPartial: true,
            paginationViaCursor: [['field' => 'createdAt', 'direction' => 'DESC']],
        ),
    ],
    graphQlOperations: [],
)]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ORM\Entity]
#[ORM\Table(name: 'issue_8085_dated_cursor_dummy')]
class DatedCursorDummy
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    public ?\DateTimeImmutable $createdAt = null;
}
