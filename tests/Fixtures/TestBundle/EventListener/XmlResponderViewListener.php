<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
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

    /**
     * @var ResourceMetadataFactoryInterface
     */
    private $resourceMetadataFactory;

    public function __construct(SerializerInterface $serializer, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->serializer = $serializer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
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

        $resourceClass = $request->attributes->get('_resource_class');
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $context = $resourceMetadata->getAttribute('normalization_context', []);

        $response = new Response(
            $this->serializer->serialize($controllerResult, self::FORMAT, $context),
            $status,
            ['Content-Type' => 'application/xml']
        );

        $event->setResponse($response);
    }
}
