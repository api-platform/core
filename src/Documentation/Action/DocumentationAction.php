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

namespace ApiPlatform\Core\Documentation\Action;

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates the API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class DocumentationAction
{
    private $resourceNameCollectionFactory;
    private $title;
    private $description;
    private $version;
    private $formats;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, string $title = '', string $description = '', string $version = '', array $formats = [])
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->formats = $formats;
    }

    public function __invoke(Request $request = null): Documentation
    {
        if (null !== $request) {
            $request->attributes->set('_api_normalization_context', $request->attributes->get('_api_normalization_context', []) + ['base_url' => $request->getBaseUrl()]);
        }

        return new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version, $this->formats);
    }
}
