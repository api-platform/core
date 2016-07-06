<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonLd\Action;

use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Generates JSON-LD contexts.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextAction
{
    const RESERVED_SHORT_NAMES = [
        'ConstraintViolationList' => true,
        'Error' => true,
    ];

    private $contextBuilder;
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;

    public function __construct(ContextBuilderInterface $contextBuilder, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->contextBuilder = $contextBuilder;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Generates a context according to the type requested.
     *
     * @throws NotFoundHttpException
     */
    public function __invoke(string $shortName) : JsonResponse
    {
        if ('Entrypoint' === $shortName) {
            return ['@context' => $this->contextBuilder->getEntrypointContext()];
        }

        if (isset(self::RESERVED_SHORT_NAMES[$shortName])) {
            return new JsonResponse(['@context' => $this->contextBuilder->getBaseContext()], JsonResponse::HTTP_OK, ['Content-Type' => 'application/ld+json']);
        }

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if ($shortName === $resourceMetadata->getShortName()) {
                return new JsonResponse(['@context' => $this->contextBuilder->getResourceContext($resourceClass)], JsonResponse::HTTP_OK, ['Content-Type' => 'application/ld+json']);
            }
        }

        throw new NotFoundHttpException();
    }
}
