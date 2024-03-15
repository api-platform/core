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

use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiFilter(RangeFilter::class, properties: ['id'])]
#[ApiFilter(OrderFilter::class, properties: ['id' => 'DESC'])]
#[ApiResource(paginationPartial: true, paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']])]
#[ODM\Document]
class SoMany
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public $id;
    #[ODM\Field(nullable: true)]
    public $content;

    #[ODM\ReferenceOne(targetDocument: FooDummy::class, storeAs: 'id', nullable: true)]
    public ?FooDummy $fooDummy;
}
