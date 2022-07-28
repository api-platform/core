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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;

/**
 * Custom Identifier Dummy.
 */
#[ApiResource(uriVariables: ['firstId' => new Link(compositeIdentifier: false, fromClass: self::class, identifiers: ['firstId']), 'secondId' => new Link(compositeIdentifier: false, fromClass: self::class, identifiers: ['secondId'])])]
#[ORM\Entity]
class CustomMultipleIdentifierDummy
{
    /**
     * @var int The custom identifier
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    private int $firstId;
    /**
     * @var int The custom identifier
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    private int $secondId;
    /**
     * @var string The dummy name
     */
    #[ORM\Column(length: 30)]
    private string $name;

    public function getFirstId(): int
    {
        return $this->firstId;
    }

    public function setFirstId(int $firstId): void
    {
        $this->firstId = $firstId;
    }

    public function getSecondId(): int
    {
        return $this->secondId;
    }

    public function setSecondId(int $secondId): void
    {
        $this->secondId = $secondId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
