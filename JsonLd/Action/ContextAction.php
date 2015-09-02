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

use Dunglas\ApiBundle\JsonLd\ContextBuilderInterface;
use Dunglas\ApiBundle\Metadata\Resource\Factory\CollectionMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Generates JSON-LD contexts.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextAction
{
    /**
     * @var array
     */
    const RESERVED_SHORT_NAMES = [
        'ConstraintViolationList' => true,
        'Error' => true,
    ];

    /**
     * @var ContextBuilderInterface
     */
    private $contextBuilder;

    /**
     * @var CollectionMetadataFactoryInterface
     */
    private $collectionMetadataFactory;

    /**
     * @var ItemMetadataFactoryInterface
     */
    private $itemMetadataFactory;

    public function __construct(ContextBuilderInterface $contextBuilder, CollectionMetadataFactoryInterface $collectionMetadataFactory, ItemMetadataFactoryInterface $itemMetadataFactory)
    {
        $this->contextBuilder = $contextBuilder;
        $this->collectionMetadataFactory = $collectionMetadataFactory;
        $this->itemMetadataFactory = $itemMetadataFactory;
    }

    /**
     * Generates a context according to the type requested.
     *
     * @param Request $request
     * @param string  $shortName
     *
     * @return array
     *
     * @throws NotFoundHttpException
     */
    public function __invoke(Request $request, string $shortName) : array
    {
        $request->attributes->set('_api_format', 'jsonld');

        if ('Entrypoint' === $shortName) {
            return ['@context' => $this->contextBuilder->getEntrypointContext()];
        }

        if (isset(self::RESERVED_SHORT_NAMES[$shortName])) {
            return ['@context' => $this->contextBuilder->getBaseContext()];
        }

        foreach ($this->collectionMetadataFactory->create() as $resourceClass) {
            $itemMetadata = $this->itemMetadataFactory->create($resourceClass);

            if ($shortName === $itemMetadata->getShortName()) {
                return ['@context' => $this->contextBuilder->getResourceContext($resourceClass)];
            }
        }

        throw new NotFoundHttpException();
    }
}
