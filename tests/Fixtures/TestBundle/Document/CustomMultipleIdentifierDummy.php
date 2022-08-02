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
use ApiPlatform\Metadata\Link;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Custom Identifier Dummy.
 */
#[ApiResource(uriVariables: ['firstId' => new Link(compositeIdentifier: false, fromClass: self::class, identifiers: ['firstId']), 'secondId' => new Link(compositeIdentifier: false, fromClass: self::class, identifiers: ['secondId'])])]
#[ODM\Document]
class CustomMultipleIdentifierDummy
{
    /**
     * @var int The custom identifier
     */
    #[ODM\Id(strategy: 'NONE', type: 'int')]
    private ?int $firstId = null;

    /**
     * @var int The custom identifier
     */
    #[ApiProperty(identifier: true)]
    #[ODM\Field(type: 'int')]
    private ?int $secondId = null;

    /**
     * @var string The dummy name
     */
    #[ODM\Field(type: 'string')]
    private ?string $name = null;

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
