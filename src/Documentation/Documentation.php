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

namespace ApiPlatform\Documentation;

use ApiPlatform\Metadata\Resource\ResourceNameCollection;

/**
 * Generates the API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class Documentation implements DocumentationInterface
{
    private $resourceNameCollection;
    private $title;
    private $description;
    private $version;

    public function __construct(ResourceNameCollection $resourceNameCollection, string $title = '', string $description = '', string $version = '')
    {
        $this->resourceNameCollection = $resourceNameCollection;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getResourceNameCollection(): ResourceNameCollection
    {
        return $this->resourceNameCollection;
    }
}
