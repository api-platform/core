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

use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

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

    public function __construct(
        private readonly ContextBuilderInterface $contextBuilder,
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly ?ProviderInterface $provider = null,
        private readonly ?ProcessorInterface $processor = null,
        private readonly ?SerializerInterface $serializer = null,
    ) {
    }

    /**
     * Generates a context according to the type requested.
     *
     * @throws NotFoundHttpException
     *
     * @return array{'@context': array<string, mixed>}|Response
     */
    public function __invoke(string $shortName = 'Entrypoint', ?Request $request = null): array|Response
    {
        if (!$shortName) {
            $shortName = 'Entrypoint';
        }

        if (null !== $request && $this->provider && $this->processor && $this->serializer) {
            $operation = new Get(
                outputFormats: ['jsonld' => ['application/ld+json']],
                validate: false,
                provider: fn () => $this->getContext($shortName),
                serialize: false,
                read: true
            );
            $context = ['request' => $request];
            $jsonLdContext = $this->provider->provide($operation, [], $context);

            return $this->processor->process($this->serializer->serialize($jsonLdContext, 'json'), $operation, [], $context);
        }

        if (!$context = $this->getContext($shortName)) {
            throw new NotFoundHttpException();
        }

        return $context;
    }

    /**
     * @return array{'@context': array<string, mixed>}|null
     */
    private function getContext(string $shortName): ?array
    {
        if ('Entrypoint' === $shortName) {
            return ['@context' => $this->contextBuilder->getEntrypointContext()];
        }

        // TODO: remove this, exceptions are resources since 3.2
        if (isset(self::RESERVED_SHORT_NAMES[$shortName])) {
            return ['@context' => $this->contextBuilder->getBaseContext()];
        }

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
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

        return null;
    }
}
