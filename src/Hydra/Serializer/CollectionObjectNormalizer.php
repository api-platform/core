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

namespace ApiPlatform\Hydra\Serializer;

use ApiPlatform\Hydra\Collection;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\JsonLd\Serializer\HydraPrefixTrait;
use ApiPlatform\JsonLd\Serializer\JsonLdContextTrait;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\ContextTrait;
use ApiPlatform\Serializer\OperationContextTrait;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes an authoritative ApiPlatform\Hydra\Collection object: only the fields the user set are emitted.
 */
final class CollectionObjectNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use ContextTrait;
    use HydraPrefixTrait;
    use JsonLdContextTrait;
    use NormalizerAwareTrait;
    use OperationContextTrait;

    public const FORMAT = 'jsonld';

    /**
     * @param array<string, mixed> $defaultContext
     */
    public function __construct(
        private readonly ContextBuilderInterface $contextBuilder,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly IriConverterInterface $iriConverter,
        private readonly array $defaultContext = [],
    ) {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $data instanceof Collection;
    }

    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? [Collection::class => true] : [];
    }

    /**
     * @param Collection $data
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $hydraPrefix = $this->getHydraPrefix($context + $this->defaultContext);

        if (!isset($context['resource_class'])) {
            throw new UnexpectedValueException(\sprintf('The "resource_class" key must be present in the context to normalize an "%s" object.', Collection::class));
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass(null, $context['resource_class']);

        $normalized = null !== $data->context
            ? ['@context' => $data->context]
            : $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);

        $normalized['@id'] = $data->id ?? $this->iriConverter->getIriFromResource($resourceClass, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context);
        $normalized['@type'] = $hydraPrefix.$data->type;

        // "totalItems" is a non-nullable, uninitialized-by-default int: isset() is the only safe way to check it was set.
        if (isset($data->totalItems)) {
            $normalized[$hydraPrefix.'totalItems'] = $data->totalItems;
        } elseif (is_countable($data->member)) {
            $normalized[$hydraPrefix.'totalItems'] = \count($data->member);
        }

        $collectionContext = $this->initContext($resourceClass, $context);
        $collectionContext['api_collection_sub_level'] = true;
        $childContext = $this->createOperationContext($collectionContext, $resourceClass);

        $members = [];
        foreach ($data->member as $item) {
            $members[] = $this->normalizer->normalize($item, $format, $childContext + ['jsonld_has_context' => true]);
        }
        $normalized[$hydraPrefix.'member'] = $members;

        if (null !== $data->view) {
            $view = [
                '@id' => $data->view->id,
                '@type' => $hydraPrefix.'PartialCollectionView',
            ];

            if (null !== $data->view->first) {
                $view[$hydraPrefix.'first'] = $data->view->first;
                $view[$hydraPrefix.'last'] = $data->view->last;
            }

            if (null !== $data->view->previous) {
                $view[$hydraPrefix.'previous'] = $data->view->previous;
            }

            if (null !== $data->view->next) {
                $view[$hydraPrefix.'next'] = $data->view->next;
            }

            $normalized[$hydraPrefix.'view'] = $view;
        }

        if (null !== $data->search) {
            $mapping = [];
            foreach ($data->search->mapping as $m) {
                $mapping[] = [
                    '@type' => 'IriTemplateMapping',
                    'variable' => $m->variable,
                    'property' => $m->property,
                    'required' => $m->required,
                ];
            }

            $normalized[$hydraPrefix.'search'] = [
                '@type' => $hydraPrefix.'IriTemplate',
                $hydraPrefix.'template' => $data->search->template,
                $hydraPrefix.'variableRepresentation' => $data->search->variableRepresentation,
                $hydraPrefix.'mapping' => $mapping,
            ];
        }

        return $normalized;
    }
}
