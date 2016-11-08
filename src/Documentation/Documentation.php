<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Documentation;

use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;

/**
 * Generates the API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class Documentation
{
    private $resourceNameCollection;
    private $title;
    private $description;
    private $version;
    private $mimeTypes = [];

    public function __construct(ResourceNameCollection $resourceNameCollection, string $title = '', string $description = '', string $version = '', array $formats = [])
    {
        $this->resourceNameCollection = $resourceNameCollection;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        foreach ($formats as $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $this->mimeTypes[] = $mimeType;
            }
        }
    }

    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
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
