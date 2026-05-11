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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ComparisonFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ApiResource(
    paginationItemsPerPage: 3,
    paginationPartial: true,
    paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
    graphQlOperations: [],
    parameters: [
        'id' => new QueryParameter(
            filter: new ComparisonFilter(new ExactFilter()),
            property: 'id',
            nativeType: new BuiltinType(TypeIdentifier::INT),
        ),
        'order[id]' => new QueryParameter(
            filter: new SortFilter(),
            property: 'id',
            nativeType: new BuiltinType(TypeIdentifier::INT),
        ),
    ],
)]
#[ORM\Entity]
class CursorPaginatedDummy
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;
}
