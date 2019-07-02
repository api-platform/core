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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\DummyObjectWithoutConstructor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy entity built with constructor.
 * https://github.com/api-platform/core/issues/1747.
 *
 * @author Maxime Veber <maxime.veber@nekland.fr>
 *
 * @ApiResource(
 *     itemOperations={
 *         "get",
 *         "put"={"denormalization_context"={"groups"={"put"}}}
 *     }
 * )
 * @ORM\Entity
 */
class DummyEntityWithConstructor
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $foo;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $bar;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     * @Groups({"put"})
     */
    private $baz;

    /**
     * @var DummyObjectWithoutConstructor[]
     */
    private $items;

    /**
     * @param DummyObjectWithoutConstructor[] $items
     */
    public function __construct(string $foo, string $bar, array $items)
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->items = $items;
    }

    public function getId(): int
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

    /**
     * @return string
     */
    public function getBaz()
    {
        return $this->baz;
    }

    public function setBaz(string $baz)
    {
        $this->baz = $baz;
    }
}
