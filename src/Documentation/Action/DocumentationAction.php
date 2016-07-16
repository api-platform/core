<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Documentation\Action;

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

/**
 * Generates the API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class DocumentationAction
{
    private $documentation;
    private $resourceNameCollectionFactory;

    public function __construct(Documentation $documentation, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory)
    {
        $this->documentation = $documentation;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
    }

    public function __invoke() : Documentation
    {
        return $this->documentation->create($this->resourceNameCollectionFactory->create());
    }
}
