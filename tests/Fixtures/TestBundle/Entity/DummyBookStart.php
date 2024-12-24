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

use ApiPlatform\Doctrine\Orm\Filter\StartSearchFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[GetCollection(
    parameters: [
        'title' => new QueryParameter(
            filter: new StartSearchFilter()
        ),
    ],
)]
#[ORM\Entity]
class DummyBookStart
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

        #[ORM\ManyToOne(targetEntity: DummyAuthorStart::class, inversedBy: 'dummyBookStarts')]
        #[ORM\JoinColumn(nullable: false)]
        public ?DummyAuthorStart $dummyAuthorStart = null,
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

    public function getDummyAuthorStart(): DummyAuthorStart
    {
        return $this->dummyAuthorStart;
    }

    public function setDummyAuthorStart(DummyAuthorStart $dummyAuthorStart): void
    {
        $this->dummyAuthorStart = $dummyAuthorStart;
    }
}
