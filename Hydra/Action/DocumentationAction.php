<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Hydra\Action;

use Dunglas\ApiBundle\Hydra\ApiDocumentationBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates the Hydra API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DocumentationAction
{
    /**
     * @var ApiDocumentationBuilderInterface
     */
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
