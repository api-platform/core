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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ApiResource(uriTemplate: '/slug_parent_dummies/{slug}/child_dummies{._format}', uriVariables: ['slug' => new Link(fromClass: SlugParentDummy::class, identifiers: ['slug'], toProperty: 'parentDummy')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/slug_child_dummies/{slug}/parent_dummy/child_dummies{._format}', uriVariables: ['slug' => new Link(fromClass: self::class, identifiers: ['slug'], fromProperty: 'parentDummy'), 'parentDummy' => new Link(fromClass: SlugParentDummy::class, identifiers: [], expandedValue: 'parent_dummy', toProperty: 'parentDummy')], status: 200, operations: [new GetCollection()])]
#[ORM\Entity]
class SlugChildDummy
{
    /**
     * @var int|null The identifier
     */
    #[ApiProperty(identifier: false)]
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @var string The slug used as API identifier
     */
    #[ApiProperty(identifier: true)]
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\ManyToOne(targetEntity: SlugParentDummy::class, inversedBy: 'childDummies')]
    #[ORM\JoinColumn(name: 'parent_dummy_id', referencedColumnName: 'id')]
    private ?SlugParentDummy $parentDummy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getParentDummy(): ?SlugParentDummy
    {
        return $this->parentDummy;
    }

    public function setParentDummy(?SlugParentDummy $parentDummy = null): self
    {
        $this->parentDummy = $parentDummy;

        return $this;
    }
}
