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

namespace ApiPlatform\Serializer\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy class that cannot be cloned.
 *
 * @author Colin O'Dell <colinodell@gmail.com>
 */
#[ApiResource]
class NonCloneableDummy
{
    /**
     * @var int|null The id
     */
    private $id;

    /**
     * @var string The dummy name
     */
    #[ApiProperty(iris: ['http://schema.org/name'])]
    #[Assert\NotBlank]
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function __clone()
    {
    }
}
