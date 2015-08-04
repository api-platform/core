<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Behat\TestBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Serializes data in XML then builds the response object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class XmlResponderViewListener
{
    const FORMAT = 'xml';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * In an API context, converts any data to a XML response.
     *
     * @param GetResponseForControllerResultEvent $event
     *
     * @return Response|mixed
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();

        if ($controllerResult instanceof Response) {
            return $controllerResult;
        }

        $request = $event->getRequest();

        $format = $request->attributes->get('_api_format');
        if (self::FORMAT !== $format) {
            return $controllerResult;
        }

        switch ($request->getMethod()) {
            case Request::METHOD_POST:
                $status = 201;
                break;

            case Request::METHOD_DELETE:
                $status = 204;
                break;

            default:
                $status = 200;
                break;
        }

        $resourceType = $request->attributes->get('_resource_type');
        $response = new Response(
            $this->serializer->serialize($controllerResult, self::FORMAT, $resourceType->getNormalizationContext()),
            $status,
            ['Content-Type' => 'application/xml']
        );

        $event->setResponse($response);
    }
}
