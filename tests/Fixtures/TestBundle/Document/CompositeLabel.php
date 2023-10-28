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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Label.
 */
#[ApiResource]
#[ODM\Document]
class CompositeLabel implements \Stringable
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[Groups(['default'])]
    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $value = null;

    /**
     * Gets id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets value.
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Sets value.
     *
     * @param string|null $value the value to set
     */
    public function setValue($value = null): void
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
