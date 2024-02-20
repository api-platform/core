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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6146;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(uriTemplate: 'issue-6146-childs/{id}'),
        new GetCollection(uriTemplate: 'issue-6146-childs'),
    ],
    normalizationContext: ['groups' => ['testgroup']],
)]
#[ORM\Entity]
class Issue6146Child
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Issue6146Parent::class, inversedBy: 'childs')]
    #[ORM\JoinColumn(nullable: false)]
    private Issue6146Parent $parent;

    #[ORM\Column(type: 'string')]
    #[Groups(['testgroup'])]
    private string $foo = 'testtest';

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setParent(Issue6146Parent $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): Issue6146Parent
    {
        return $this->parent;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
