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

use ApiPlatform\Doctrine\Orm\Filter\ExactSearchFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[GetCollection(
    parameters: [
        'dummyAuthorExact' => new QueryParameter(
            filter: new ExactSearchFilter()
        ),
        'title' => new QueryParameter(
            filter: new ExactSearchFilter()
        ),
    ],
)]
#[ORM\Entity]
class DummyBookExact
{
    public function __construct(
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        #[ORM\Column]
        public ?int $id = null,

        #[ORM\Column]
        public ?string $title = null,

        #[ORM\Column]
        public ?string $isbn = null,

        #[ORM\ManyToOne(targetEntity: DummyAuthorExact::class, inversedBy: 'dummyBookExacts')]
        #[ORM\JoinColumn(nullable: false)]
        public ?DummyAuthorExact $dummyAuthorExact = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): void
    {
        $this->isbn = $isbn;
    }

    public function getDummyAuthorExact(): DummyAuthorExact
    {
        return $this->dummyAuthorExact;
    }

    public function setDummyAuthorExact(DummyAuthorExact $dummyAuthorExact): void
    {
        $this->dummyAuthorExact = $dummyAuthorExact;
    }
}
