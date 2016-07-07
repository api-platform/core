<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Api\RequestAttributesExtractor;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Updates the entity retrieved by the data provider with data contained in the request body.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DeserializeListener
{
    private $serializer;
    private $serializerContextBuilder;

    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
    }

    /**
     * Deserializes the data sent in the requested format.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT], true)) {
            return;
        }

        try {
            $attributes = RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
            return;
        }

        $context = $this->serializerContextBuilder->createFromRequest($request, false, $attributes);

        $data = $request->attributes->get('data');
        if (null !== $data) {
            $context['object_to_populate'] = $data;
        }

        $request->attributes->set(
            'data',
            $this->serializer->deserialize(
                $request->getContent(), $attributes['resource_class'], $attributes['format'], $context
            )
        );
    }
}
