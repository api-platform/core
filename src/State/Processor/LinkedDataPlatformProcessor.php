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
    private const DEFAULT_ALLOWED_METHODS = ['OPTIONS', 'HEAD'];

    /**
     * @param ProcessorInterface<T1, T2> $decorated
     */
    public function __construct(
        private readonly ProcessorInterface $decorated,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $response = $this->decorated->process($data, $operation, $uriVariables, $context);
        if (
            !$response instanceof Response
            || !$operation instanceof HttpOperation
            || $operation instanceof Error
            || !$operation->getUriTemplate()
            || !$this->resourceClassResolver->isResourceClass($operation->getClass())
        ) {
            return $response;
        }

        $acceptPost = null;
        $allowedMethods = self::DEFAULT_ALLOWED_METHODS;
        $resourceCollection = $this->resourceMetadataCollectionFactory->create($operation->getClass());
        foreach ($resourceCollection as $resource) {
            foreach ($resource->getOperations() as $op) {
                if ($op->getUriTemplate() === $operation->getUriTemplate()) {
                    $allowedMethods[] = $method = $op->getMethod();
                    if ('POST' === $method && \is_array($outputFormats = $op->getOutputFormats())) {
                        $acceptPost = implode(', ', array_merge(...array_values($outputFormats)));
                    }
                }
            }
        }
        if ($acceptPost) {
            $response->headers->set('Accept-Post', $acceptPost);
        }

        $response->headers->set('Allow', implode(', ', $allowedMethods));

        return $response;
    }
}
