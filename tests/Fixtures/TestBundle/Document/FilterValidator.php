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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\ArrayItemsFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\BoundsFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\EnumFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\LengthFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\MultipleOfFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\PatternFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\RequiredAllowEmptyFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\RequiredFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Filter Validator entity.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
#[ApiResource(filters: [ArrayItemsFilter::class, BoundsFilter::class, EnumFilter::class, LengthFilter::class, MultipleOfFilter::class, PatternFilter::class, RequiredFilter::class, RequiredAllowEmptyFilter::class])]
#[ODM\Document]
class FilterValidator
{
    /**
     * @var int The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    /**
     * @var string A name
     */
    #[ApiProperty(types: ['https://schema.org/name'])]
    #[ODM\Field]
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
