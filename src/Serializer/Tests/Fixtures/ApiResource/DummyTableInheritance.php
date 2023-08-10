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

namespace ApiPlatform\Serializer\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
class DummyTableInheritance
{
    /**
     * @var int|null The id
     */
    #[Groups(['default'])]
    private ?int $id = null;
    /**
     * @var string The dummy name
     */
    #[Groups(['default'])]
    private string $name;
    private ?DummyTableInheritanceRelated $parent = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?DummyTableInheritanceRelated
    {
        return $this->parent;
    }

    public function setParent(?DummyTableInheritanceRelated $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
