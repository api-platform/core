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

/**
 * Dummy with iri_only.
 *
 * @author Pierre Thibaudeau <pierre.thibaudeau@les-tilleuls.coop>
 * @ODM\Document
 */
#[ApiResource(normalizationContext: ['iri_only' => true, 'jsonld_embed_context' => true])]
class IriOnlyDummy
{
    /**
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;
    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    private $foo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFoo(): ?string
    {
        return $this->foo;
    }

    public function setFoo(string $foo): void
    {
        $this->foo = $foo;
    }
}
