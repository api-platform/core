<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle;

use Symfony\Component\Routing\RouterInterface;

/**
 * JSON-LD Context Builder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ContextBuilder
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Builds the JSON-LD context for the given resource.
     *
     * @param Resource $resource
     *
     * @return array
     */
    public function buildContext(Resource $resource)
    {
        $context = [];
        $context['@vocab'] = $this->router->generate('json_ld_api_vocab', [], RouterInterface::ABSOLUTE_URL).'#';

        return $context;
    }
}
