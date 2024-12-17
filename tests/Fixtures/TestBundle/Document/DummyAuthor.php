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

use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[GetCollection]
#[ODM\Document]
class DummyAuthor
{
    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
        public ?int $id = null,

        #[ODM\Field(type: 'string')]
        public ?string $name = null,

        #[ODM\ReferenceMany(targetDocument: DummyBook::class, cascade: ['persist'], mappedBy: 'dummyAuthor')]
        public ?Collection $dummyBooks = new ArrayCollection(),
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDummyBooks(): Collection
    {
        return $this->dummyBooks;
    }
}
