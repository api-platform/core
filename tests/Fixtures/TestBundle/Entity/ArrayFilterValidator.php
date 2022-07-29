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
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\ArrayRequiredFilter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Filter Validator entity.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
#[ApiResource(filters: [ArrayRequiredFilter::class])]
#[ORM\Entity]
class ArrayFilterValidator
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @var string A name
     */
    #[ApiProperty(types: ['https://schema.org/name'])]
    #[ORM\Column]
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
