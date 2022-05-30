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
use ApiPlatform\Doctrine\Odm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ApiFilter (NumericFilter::class, properties={"nameConverted"})
 * @ApiFilter (RangeFilter::class, properties={"nameConverted"})
 * @ApiFilter (OrderFilter::class, properties={"nameConverted"})
 */
#[ApiResource]
class ConvertedInteger
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private ?int $id = null;
    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    public $nameConverted;

    public function getId()
    {
        return $this->id;
    }
}
