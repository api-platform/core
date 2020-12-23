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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\ArrayItemsFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\BoundsFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\EnumFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\LengthFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\MultipleOfFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\PatternFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\RequiredAllowEmptyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\RequiredFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Filter Validator entity.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @ApiResource(attributes={
 *     "filters"={
 *         ArrayItemsFilter::class,
 *         BoundsFilter::class,
 *         EnumFilter::class,
 *         LengthFilter::class,
 *         MultipleOfFilter::class,
 *         PatternFilter::class,
 *         RequiredFilter::class,
 *         RequiredAllowEmptyFilter::class
 *     }
 * })
 * @ODM\Document
 */
class FilterValidator
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var string A name
     *
     * @ODM\Field
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
