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
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(normalizationContext: ['groups' => ['initial']], fetchPartial: true)]
#[ORM\Entity(readOnly: true)]
class DummyContext
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\OneToOne(targetEntity: DummyContextRelated::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    #[Groups(['initial'])]
    #[Context(normalizationContext: ['groups' => ['context_switched']])]
    private DummyContextRelated $relatedWithSwitch;

    #[ORM\OneToOne(targetEntity: DummyContextRelated::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    #[Groups(['initial'])]
    private DummyContextRelated $relatedWithoutSwitch;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getRelatedWithSwitch(): DummyContextRelated
    {
        return $this->relatedWithSwitch;
    }

    public function setRelatedWithSwitch(DummyContextRelated $relatedWithSwitch): void
    {
        $this->relatedWithSwitch = $relatedWithSwitch;
    }

    public function getRelatedWithoutSwitch(): DummyContextRelated
    {
        return $this->relatedWithoutSwitch;
    }

    public function setRelatedWithoutSwitch(DummyContextRelated $relatedWithoutSwitch): void
    {
        $this->relatedWithoutSwitch = $relatedWithoutSwitch;
    }
}
