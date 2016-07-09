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

use ApiPlatform\Core\Documentation\ApiDocumentationBuilderInterface;

/**
 * Generates the Hydra API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DocumentationAction
{
    private $apiDocumentationBuilder;

    public function __construct(ApiDocumentationBuilderInterface $apiDocumentationBuilder)
    {
        $this->apiDocumentationBuilder = $apiDocumentationBuilder;
    }

    /**
     * Gets API doc.
     */
    public function __invoke() : array
    {
        return $this->apiDocumentationBuilder->getApiDocumentation();
    }
}
