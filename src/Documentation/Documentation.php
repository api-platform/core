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
    private $info = [];
    private $mimeTypes = [];

    public function __construct(ResourceNameCollection $resourceNameCollection, array $info = [], array $formats = null)
    {
        $this->resourceNameCollection = $resourceNameCollection;
        $this->info = $info;

        if (null === $formats) {
            return;
        }

        @trigger_error(sprintf('Passing a 5th parameter to the constructor of "%s" is deprecated since API Platform 2.5', __CLASS__), E_USER_DEPRECATED);

        foreach ($formats as $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $this->mimeTypes[] = $mimeType;
            }
        }
    }

    public function getMimeTypes(): array
    {
        @trigger_error(sprintf('The method "%s" is deprecated since API Platform 2.5, use the "formats" attribute instead', __METHOD__), E_USER_DEPRECATED);

        return $this->mimeTypes;
    }

    public function getVersion(): string
    {
        return $this->info['version'] ?? '';
    }

    public function getDescription(): string
    {
        return $this->info['description'] ?? '';
    }

    public function getTitle(): string
    {
        return $this->info['title'] ?? '';
    }

    public function getContact(): array
    {
        return $this->info['contact'] ?? [];
    }

    public function getLicense(): array
    {
        return $this->info['license'] ?? [];
    }

    public function getTermsOfService(): string
    {
        return $this->info['termsOfService'] ?? '';
    }

    public function getResourceNameCollection(): ResourceNameCollection
    {
        return $this->resourceNameCollection;
    }
}
