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

use ApiPlatform\Core\Event\PostSerializeEvent;
use ApiPlatform\Core\Event\PreSerializeEvent;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Serializer\ResourceList;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder, EventDispatcherInterface $dispatcher)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Serializes the data to the requested format.
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response) {
            return;
        }

        if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            $this->serializeRawData($event, $request, $controllerResult);

            return;
        }

        $context = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);
        if ($included = $request->attributes->get('_api_included')) {
            $context['api_included'] = $included;
        }
        $resources = new ResourceList();
        $context['resources'] = &$resources;
        if (isset($context['output_class'])) {
            $context['resource_class'] = $context['output_class'];
        }

        $request->attributes->set('_api_normalization_context', $context);

        $this->dispatcher->dispatch(PreSerializeEvent::NAME, new PreSerializeEvent($this->serializer, $this->serializerContextBuilder));

        $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $context));

        $this->dispatcher->dispatch(PostSerializeEvent::NAME, new PostSerializeEvent($this->serializer, $this->serializerContextBuilder));

        $request->attributes->set('_api_respond', true);
        $request->attributes->set('_resources', $request->attributes->get('_resources', []) + (array) $resources);

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
        if (!$request->attributes->get('_api_respond')) {
            return;
        }

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
