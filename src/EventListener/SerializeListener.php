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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Serializer\ResourceList;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializeListener
{
    private $serializer;
    private $serializerContextBuilder;

    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
    }

    /**
     * Serializes the data to the requested format.
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response || !$request->attributes->getBoolean('_api_respond', true)) {
            return;
        }

        if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            $this->serializeRawData($event, $request, $controllerResult);

            return;
        }

        $request->attributes->set('_api_respond', true);
        $context = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);

        if (isset($context['output_class'])) {
            if (false === $context['output_class']) {
                // If the output class is explicitly set to false, the response must be empty
                $event->setControllerResult('');

                return;
            }

            $context['resource_class'] = $context['output_class'];
        }

        if ($included = $request->attributes->get('_api_included')) {
            $context['api_included'] = $included;
        }
        $resources = new ResourceList();
        $context['resources'] = &$resources;

        $resourcesToPush = new ResourceList();
        $context['resources_to_push'] = &$resourcesToPush;

        $request->attributes->set('_api_normalization_context', $context);

        $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $context));

        $request->attributes->set('_resources', $request->attributes->get('_resources', []) + (array) $resources);
        if (!\count($resourcesToPush)) {
            return;
        }

        $linkProvider = $request->attributes->get('_links', new GenericLinkProvider());
        foreach ($resourcesToPush as $resourceToPush) {
            $linkProvider = $linkProvider->withLink(new Link('preload', $resourceToPush));
        }
        $request->attributes->set('_links', $linkProvider);
    }

    /**
     * Tries to serialize data that are not API resources (e.g. the entrypoint or data returned by a custom controller).
     *
     * @param object $controllerResult
     *
     * @throws RuntimeException
     */
    private function serializeRawData(GetResponseForControllerResultEvent $event, Request $request, $controllerResult)
    {
        if (\is_object($controllerResult)) {
            $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $request->attributes->get('_api_normalization_context', [])));

            return;
        }

        if (!$this->serializer instanceof EncoderInterface) {
            throw new RuntimeException(sprintf('The serializer instance must implements the "%s" interface.', EncoderInterface::class));
        }

        $event->setControllerResult($this->serializer->encode($controllerResult, $request->getRequestFormat()));
    }
}
