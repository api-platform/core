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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\RuntimeResource;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'runtime_attribute_definition')]
class AttributeDefinition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $position;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'attributes')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private ResourceDefinition $definition,
        #[ORM\Column]
        private string $name,
        #[ORM\Column]
        private string $type,
        #[ORM\Column]
        private bool $required = false,
        #[ORM\Column(name: 'is_collection')]
        private bool $collection = false,
        #[ORM\Column(type: 'json')]
        private array $options = [],
        #[ORM\Column]
        private bool $filterable = false,
    ) {
        $this->position = $definition->getAttributes()->count();
        $definition->getAttributes()->add($this);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
