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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\DummyObjectWithoutConstructor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy entity built with constructor.
 *
 * https://github.com/api-platform/core/issues/1747.
 *
 * @author Maxime Veber <maxime.veber@nekland.fr>
 */
#[ApiResource(operations: [new Get(), new Put(denormalizationContext: ['groups' => ['put']]), new Post(), new GetCollection()])]
#[ORM\Entity]
class DummyEntityWithConstructor
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(nullable: true)]
    #[Groups(['put'])]
    private ?string $baz = null;

    /**
     * @param DummyObjectWithoutConstructor[] $items
     */
    public function __construct(#[ORM\Column] private string $foo, #[ORM\Column] private string $bar, private array $items)
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function getBar(): string
    {
        return $this->bar;
    }

    /**
     * @return DummyObjectWithoutConstructor[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getBaz(): ?string
    {
        return $this->baz;
    }

    public function setBaz(string $baz): void
    {
        $this->baz = $baz;
    }
}
