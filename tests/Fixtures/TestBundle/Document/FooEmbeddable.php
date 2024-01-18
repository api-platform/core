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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Embeddable Foo.
 */
#[ODM\EmbeddedDocument]
class FooEmbeddable
{
    /**
     * @var string|null The dummy name
     */
    #[ApiProperty(identifier: true)]
    #[ODM\Field(type: 'string')]
    private ?string $dummyName = null;

    #[ODM\Field(nullable: true)]
    private $nonWritableProp;

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
