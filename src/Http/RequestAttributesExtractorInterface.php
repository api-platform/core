<?php

/*
 *  This file is part of the API Platform project.
 *
 *  (c) Kévin Dunglas <dunglas@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Http;

use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
interface RequestAttributesExtractorInterface
{
    /**
     * Extract resource class, operation name and format request attributes. Throws an exception if the request does
     * not contain required attributes.
     *
     * @param Request $request
     *
     * @throws RuntimeException
     *
     * @return AttributesBag
     */
    public function extract(Request $request);
}
