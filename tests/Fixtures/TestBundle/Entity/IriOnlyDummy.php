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
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy with iri_only.
 *
 * @author Pierre Thibaudeau <pierre.thibaudeau@les-tilleuls.coop>
 */
#[ApiResource(normalizationContext: ['iri_only' => true, 'jsonld_embed_context' => true])]
#[ORM\Entity]
class IriOnlyDummy
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(type: 'string')]
    private string $foo;

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
