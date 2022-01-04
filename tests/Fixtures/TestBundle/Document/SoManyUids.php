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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Ramsey\Uuid\Nonstandard\UuidV6;

/**
 * @ODM\Document
 * @ApiResource(attributes={
 *     "pagination_partial"=true,
 *     "pagination_via_cursor"={
 *         {"field"="id", "direction"="DESC"}
 *     }
 * })
 *
 * @ApiFilter(RangeFilter::class, properties={"id"})
 * @ApiFilter(OrderFilter::class, properties={"id"="DESC"})
 */
class SoManyUids
{
    /**
     * @ODM\Id(type="uuid")
     */
    public $id;

    /**
     * @ODM\Field(nullable=true)
     */
    public $content;

    public function __construct($id)
    {
        if ($id) {
            $this->id = UuidV6::fromString($id);
        } else {
            $this->id = UuidV6::uuid6();
        }
    }
}
