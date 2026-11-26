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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'runtime_resource_definition')]
class ResourceDefinition
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    /** @var Collection<int, AttributeDefinition> */
    #[ODM\EmbedMany(targetDocument: AttributeDefinition::class)]
    private Collection $attributes;

    public function __construct(
        #[ODM\Field]
        #[ODM\UniqueIndex]
        private string $name,
        #[ODM\Field]
        private string $label = '',
        #[ODM\Field(nullable: true)]
        private ?string $security = null,
    ) {
        $this->attributes = new ArrayCollection();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function addAttribute(string $name, string $type, bool $required = false, bool $collection = false, array $options = [], bool $filterable = false): self
    {
        $this->attributes->add(new AttributeDefinition($name, $type, $required, $collection, $options, $filterable));

        return $this;
    }
}
