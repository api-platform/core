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

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[  ODM\Document]
class TransformedDummyDocument
{
    #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
    private ?int $id = null;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeInterface $date;

    public function __construct(?\DateTimeInterface $date = null)
    {
        $this->setDate($date ?? new \DateTimeImmutable());
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
