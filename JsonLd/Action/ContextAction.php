<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\Action;

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\JsonLd\ContextBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Generates JSON-LD contexts.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ContextAction
{
    /**
     * @var array
     */
    private static $reservedShortNames = [
        'ConstraintViolationList' => true,
        'Error' => true,
    ];

    /**
     * @var ContextBuilder
     */
    private $contextBuilder;
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceTypeCollection;

    public function __construct(ContextBuilder $contextBuilder, ResourceCollectionInterface $resourceTypeCollection)
    {
        $this->contextBuilder = $contextBuilder;
        $this->resourceTypeCollection = $resourceTypeCollection;
    }

    /**
     * Generates a context according to the type requested.
     *
     * @param Request $request
     * @param $shortName
     *
     * @return array
     */
    public function __invoke(Request $request, $shortName)
    {
        $request->attributes->set('_api_format', 'jsonld');

        if ('Entrypoint' === $shortName) {
            return ['@context' => $this->contextBuilder->getEntrypointContext()];
        }

        if (isset(self::$reservedShortNames[$shortName])) {
            $resource = null;
        } else {
            $resource = $this->resourceTypeCollection->getResourceForShortName($shortName);

            if (!$resource) {
                throw new NotFoundHttpException();
            }
        }

        return ['@context' => $this->contextBuilder->getContext($resource)];
    }
}
