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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Serializer\ResourceList;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * Serializes data.
 *
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializeProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<T1, T2> $processor
     */
    public function __construct(private readonly ProcessorInterface $processor, private readonly SerializerInterface $serializer, private readonly SerializerContextBuilderInterface $serializerContextBuilder)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Response || !$operation->canSerialize() || !($request = $context['request'] ?? null)) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        // @see ApiPlatform\State\Processor\RespondProcessor
        $context['original_data'] = $data;

        $serializerContext = $this->serializerContextBuilder->createFromRequest($request, true, [
            'resource_class' => $operation->getClass(),
            'operation' => $operation,
        ]);

        $serializerContext['uri_variables'] = $uriVariables;

        if (isset($serializerContext['output']) && \array_key_exists('class', $serializerContext['output']) && null === $serializerContext['output']['class']) {
            return $this->processor->process(null, $operation, $uriVariables, $context);
        }

        $resources = new ResourceList();
        $serializerContext['resources'] = &$resources;
        $serializerContext[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources';

        $resourcesToPush = new ResourceList();
        $serializerContext['resources_to_push'] = &$resourcesToPush;
        $serializerContext[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources_to_push';

        $serialized = $this->serializer->serialize($data, $request->getRequestFormat(), $serializerContext);
        $request->attributes->set('_resources', $request->attributes->get('_resources', []) + (array) $resources);
        if (\count($resourcesToPush)) {
            $linkProvider = $request->attributes->get('_api_platform_links', new GenericLinkProvider());
            foreach ($resourcesToPush as $resourceToPush) {
                $linkProvider = $linkProvider->withLink((new Link('preload', $resourceToPush))->withAttribute('as', 'fetch'));
            }
            $request->attributes->set('_api_platform_links', $linkProvider);
        }

        return $this->processor->process($serialized, $operation, $uriVariables, $context);
    }
}
