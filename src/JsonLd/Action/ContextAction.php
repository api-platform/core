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

namespace ApiPlatform\JsonLd\Action;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Generates JSON-LD contexts.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextAction
{
    public const RESERVED_SHORT_NAMES = [
        'ConstraintViolationList' => true,
        'Error' => true,
    ];

    private $contextBuilder;
    private $resourceNameCollectionFactory;
    private $resourceMetadataCollectionFactory;

    public function __construct(ContextBuilderInterface $contextBuilder, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory)
    {
        $this->contextBuilder = $contextBuilder;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * Generates a context according to the type requested.
     *
     * @throws NotFoundHttpException
     */
    public function __invoke(string $shortName): array
    {
        if ('Entrypoint' === $shortName) {
            return ['@context' => $this->contextBuilder->getEntrypointContext()];
        }

        if (isset(self::RESERVED_SHORT_NAMES[$shortName])) {
            return ['@context' => $this->contextBuilder->getBaseContext()];
        }

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            /** @var ResourceMetadataCollection */
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);

            try {
                $resourceMetadataCollection = $resourceMetadataCollection->getOperation();
            } catch (OperationNotFoundException) {
                continue;
            }

            if ($shortName === $resourceMetadataCollection->getShortName()) {
                return ['@context' => $this->contextBuilder->getResourceContext($resourceClass)];
            }
        }

        throw new NotFoundHttpException();
    }
}
