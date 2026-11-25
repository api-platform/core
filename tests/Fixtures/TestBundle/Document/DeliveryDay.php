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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

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
#[ODM\Document]
class DeliveryDay
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public ?int $id = null;

    #[ODM\ReferenceOne(targetDocument: Supplier::class, inversedBy: 'deliveryDays', storeAs: 'id')]
    public ?Supplier $supplier = null;

    #[ODM\Field]
    public string $day = '';
}
