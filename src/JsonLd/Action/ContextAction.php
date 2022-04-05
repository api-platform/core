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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
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
    /**
     * @var ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface|null
     */
    private $resourceMetadataFactory;

    public function __construct(ContextBuilderInterface $contextBuilder, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, $resourceMetadataFactory)
    {
        $this->contextBuilder = $contextBuilder;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }
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
            /** @var ResourceMetadata|ResourceMetadataCollection */
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if ($resourceMetadata instanceof ResourceMetadataCollection) {
                try {
                    $resourceMetadata = $resourceMetadata->getOperation();
                } catch (OperationNotFoundException $e) {
                    continue;
                }
            }

            if ($shortName === $resourceMetadata->getShortName()) {
                return ['@context' => $this->contextBuilder->getResourceContext($resourceClass)];
            }
        }

        throw new NotFoundHttpException();
    }
}

class_alias(ContextAction::class, \ApiPlatform\Core\JsonLd\Action\ContextAction::class);
