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
use Doctrine\ORM\Mapping as ORM;

/**
 * Filter Validator entity.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
#[ApiResource(filters: [ArrayItemsFilter::class, BoundsFilter::class, EnumFilter::class, LengthFilter::class, MultipleOfFilter::class, PatternFilter::class, RequiredFilter::class, RequiredAllowEmptyFilter::class])]
#[ORM\Entity]
class FilterValidator
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ApiProperty(types: ['http://schema.org/name'])]
    #[ORM\Column]
    private string $name;

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
