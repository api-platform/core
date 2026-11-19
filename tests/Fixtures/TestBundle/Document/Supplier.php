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
use ApiPlatform\Metadata\Get;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(operations: [new Get()])]
#[ODM\Document]
class Supplier
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public ?int $id = null;

    #[ODM\Field]
    public string $name = '';

    /** @var Collection<int, DeliveryDay> */
    #[ODM\ReferenceMany(targetDocument: DeliveryDay::class, mappedBy: 'supplier')]
    public Collection $deliveryDays;

    public function __construct()
    {
        $this->deliveryDays = new ArrayCollection();
    }
}
