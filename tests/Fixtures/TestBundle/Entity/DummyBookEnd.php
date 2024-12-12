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

use ApiPlatform\Doctrine\Orm\Filter\EndSearchFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[GetCollection(
    parameters: [
        'title' => new QueryParameter(
            filter: new EndSearchFilter()
        ),
    ],
)]
#[ORM\Entity]
class DummyBookEnd
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

        #[ORM\ManyToOne(targetEntity: DummyAuthorEnd::class, inversedBy: 'dummyBookEnds')]
        #[ORM\JoinColumn(nullable: false)]
        public ?DummyAuthorEnd $dummyAuthorEnd = null,
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

    public function getDummyAuthorEnd(): DummyAuthorEnd
    {
        return $this->dummyAuthorEnd;
    }

    public function setDummyAuthorEnd(DummyAuthorEnd $dummyAuthorEnd): void
    {
        $this->dummyAuthorEnd = $dummyAuthorEnd;
    }
}
