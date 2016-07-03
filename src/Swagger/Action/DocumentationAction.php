<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Action;

use ApiPlatform\Core\Util\ApiDocumentationBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates the Swagger API documentation.
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
     *
     * @param Request $request
     *
     * @return array
     */
    public function __invoke(Request $request)
    {
        $request->attributes->set('_api_format', 'jsonld');

        return $this->apiDocumentationBuilder->getApiDocumentation();
    }
}
