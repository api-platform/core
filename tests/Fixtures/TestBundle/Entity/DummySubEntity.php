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

#[ORM\Entity]
class DummySubEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private string $strId;

    #[ORM\Column]
    private string $name;

    #[ORM\OneToOne(inversedBy: 'subEntity', cascade: ['persist'])]
    private ?DummyWithSubEntity $mainEntity = null;

    public function __construct($strId, $name)
    {
        $this->strId = $strId;
        $this->name = $name;
    }

    public function getStrId(): string
    {
        return $this->strId;
    }

    public function getMainEntity(): ?DummyWithSubEntity
    {
        return $this->mainEntity;
    }

    public function setMainEntity(DummyWithSubEntity $mainEntity): void
    {
        $this->mainEntity = $mainEntity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
