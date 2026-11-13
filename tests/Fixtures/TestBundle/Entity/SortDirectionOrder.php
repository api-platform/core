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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

if (enum_exists('SortDirection')) {
    #[ApiResource(
        operations: [
            new GetCollection(uriTemplate: '/sort_direction_orders'),
            new GetCollection(uriTemplate: '/sort_direction_orders_desc', order: ['name' => \SortDirection::Descending]),
        ],
        order: ['name' => \SortDirection::Ascending],
        paginationEnabled: false,
    )]
    #[ORM\Entity]
    class SortDirectionOrder
    {
        #[ORM\Id]
        #[ORM\Column]
        #[ORM\GeneratedValue]
        public ?int $id = null;

        #[ORM\Column]
        public string $name;
    }
}
