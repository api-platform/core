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

namespace ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 */
final class LinkedDataPlatformProcessor implements ProcessorInterface
{
    private const DEFAULT_ALLOWED_METHOD = ['OPTIONS', 'HEAD'];

    /**
     * @param ProcessorInterface<T1, T2> $decorated
     */
    public function __construct(
        private readonly ProcessorInterface $decorated, // todo is processor interface nullable
        private readonly ?ResourceClassResolverInterface $resourceClassResolver = null,
        private readonly ?ResourceMetadataCollectionFactoryInterface $resourceCollectionMetadataFactory = null,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $response = $this->decorated->process($data, $operation, $uriVariables, $context);
        if (
            !$response instanceof Response
            || !$operation instanceof HttpOperation
            || $operation instanceof Error
            || null === $this->resourceCollectionMetadataFactory
            || !($context['resource_class'] ?? null)
            || null === $operation->getUriTemplate()
            || !$this->resourceClassResolver?->isResourceClass($context['resource_class'])
        ) {
            return $response;
        }

        $allowedMethods = self::DEFAULT_ALLOWED_METHOD;
        $resourceMetadataCollection = $this->resourceCollectionMetadataFactory->create($context['resource_class']);
        foreach ($resourceMetadataCollection as $resource) {
            foreach ($resource->getOperations() as $resourceOperation) {
                if ($resourceOperation->getUriTemplate() === $operation->getUriTemplate()) {
                    $operationMethod = $resourceOperation->getMethod();
                    $allowedMethods[] = $operationMethod;
                    if ('POST' === $operationMethod && \is_array($outputFormats = $operation->getOutputFormats())) {
                        $response->headers->set('Accept-Post', implode(', ', array_merge(...array_values($outputFormats))));
                    }
                }
            }
        }

        $response->headers->set('Allow', implode(', ', $allowedMethods));

        return $response;
    }
}
