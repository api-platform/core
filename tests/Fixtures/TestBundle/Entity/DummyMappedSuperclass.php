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

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
class DummyMappedSuperclass
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $foo = null;

    public function getFoo(): ?string
    {
        return $this->foo;
    }

    public function setFoo(?string $foo): static
    {
        $this->foo = $foo;

        return $this;
    }
}
