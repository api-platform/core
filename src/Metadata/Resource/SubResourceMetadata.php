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

namespace ApiPlatform\Core\Metadata\Resource;

final class SubResourceMetadata
{
    private $parent;
    private $property;
    private $isCollection;
    private $parentResourceClass;

    public function __construct(ResourceMetadata $parent, string $property, bool $isCollection, string $parentResourceClass)
    {
        $this->parent = $parent;
        $this->property = $property;
        $this->isCollection = $isCollection;
        $this->parentResourceClass = $parentResourceClass;
    }

    /**
     * @return ResourceMetadata
     */
    public function getParent(): ResourceMetadata
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return $this->property;
    }

    /**
     * @return bool
     */
    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    /**
     * @return string
     */
    public function getParentResourceClass(): string
    {
        return $this->parentResourceClass;
    }
}
