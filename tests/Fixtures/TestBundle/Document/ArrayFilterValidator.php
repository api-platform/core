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
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\ArrayRequiredFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Filter Validator entity.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
#[ApiResource(filters: [ArrayRequiredFilter::class])]
#[ODM\Document]
class ArrayFilterValidator
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ApiProperty(types: ['https://schema.org/name'])]
    #[ODM\Field]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
