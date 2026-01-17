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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource]
#[ORM\Entity(readOnly: true)]
class DummyContextRelated
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(type: 'string')]
    #[Groups(['context_switched'])]
    private string $contextSwitched;

    #[ORM\Column(type: 'string')]
    #[Groups(['initial'])]
    private string $initialGroups;

    #[ORM\Column(type: 'string')]
    private string $noGroups;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getContextSwitched(): string
    {
        return $this->contextSwitched;
    }

    public function setContextSwitched(string $contextSwitched): void
    {
        $this->contextSwitched = $contextSwitched;
    }

    public function getInitialGroups(): string
    {
        return $this->initialGroups;
    }

    public function setInitialGroups(string $initialGroups): void
    {
        $this->initialGroups = $initialGroups;
    }

    public function getNoGroups(): string
    {
        return $this->noGroups;
    }

    public function setNoGroups(string $noGroups): void
    {
        $this->noGroups = $noGroups;
    }
}
