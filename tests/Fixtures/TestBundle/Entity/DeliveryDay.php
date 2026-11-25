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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedDeliveryDay;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new Post(),
        new Get(),
        new GetCollection(
            uriTemplate: '/suppliers/{supplierId}/delivery_days',
            uriVariables: [
                'supplierId' => new Link(fromProperty: 'deliveryDays', fromClass: Supplier::class),
            ],
        ),
        new Put(),
        new Delete(),
    ],
)]
#[ORM\Entity]
#[Map(target: MappedDeliveryDay::class)]
class DeliveryDay
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deliveryDays')]
    public ?Supplier $supplier = null;

    #[ORM\Column]
    public string $day = '';
}
