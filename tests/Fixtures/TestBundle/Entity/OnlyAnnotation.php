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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource
 *
 * @ApiFilter(SearchFilter::class, properties={"id"})
 */
class OnlyAnnotation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"chicago", "friends"})
     * @ApiProperty(writable=false)
     * @ApiFilter(DateFilter::class)
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Groups({"barcelona", "chicago", "friends"})
     *
     * @ApiSubresource
     *
     * @ApiProperty(iri="RelatedDummy.name")
     * @ApiFilter(SearchFilter::class)
     * @ApiFilter(ExistsFilter::class)
     */
    private $name;
}
