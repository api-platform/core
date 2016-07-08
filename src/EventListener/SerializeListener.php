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
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response) {
            return;
        }

        if ($request->attributes->get('_api_respond') && !is_object($controllerResult)) {
            if (!$this->serializer instanceof EncoderInterface) {
                throw new RuntimeException('The serializer instance must implements the "%s" interface.', EncoderInterface::class);
            }

            $event->setControllerResult($this->serializer->encode($controllerResult, $request->getRequestFormat()));
        }

        try {
            $attributes = RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
            return;
        }

        $context = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);
        $request->attributes->set('_api_respond', true);

        $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $context));
    }
}
