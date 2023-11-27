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
use Doctrine\ORM\Mapping as ORM;

/**
 * Embeddable Foo.
 *
 * @author Jordan Samouh <jordan.samouh@gmail.com>
 */
#[ApiResource(operations: [], graphQlOperations: [])]
#[ORM\Embeddable]
class FooEmbeddable
{
    /**
     * @var string The dummy name
     */
    #[ApiProperty(identifier: true)]
    #[ORM\Column(nullable: true)]
    private ?string $dummyName = null;

    #[ORM\Column(nullable: true)]
    private $nonWritableProp; // @phpstan-ignore-line

    public function __construct()
    {
    }

    public function getDummyName(): ?string
    {
        return $this->dummyName;
    }

    public function setDummyName(string $dummyName): void
    {
        $this->dummyName = $dummyName;
    }

    public function getNonWritableProp()
    {
        return $this->nonWritableProp;
    }
}
