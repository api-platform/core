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

use ApiPlatform\Doctrine\Odm\Filter\ExactSearchFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[GetCollection(
    parameters: [
        'dummyAuthorExact' => new QueryParameter(
            filter: new ExactSearchFilter()
        ),
    ],
)]
#[ODM\Document]
class DummyBookExact
{
    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
        public ?int $id = null,

        #[ODM\Field(type: 'string')]
        public ?string $title = null,

        #[ODM\Field(type: 'string')]
        public ?string $isbn = null,

        #[ODM\ReferenceOne(storeAs: 'id', targetDocument: DummyAuthorExact::class, inversedBy: 'dummyBookExacts')]
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
