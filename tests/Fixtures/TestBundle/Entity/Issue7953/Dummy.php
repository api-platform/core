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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7953;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiFilter(RangeFilter::class, properties: ['id'])]
#[ApiFilter(OrderFilter::class, properties: ['id' => 'DESC'])]
#[ApiResource(
    shortName: 'Issue7953',
    paginationItemsPerPage: 3,
    paginationPartial: true,
    paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
)]
#[ORM\Entity]
class Dummy
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;
}
