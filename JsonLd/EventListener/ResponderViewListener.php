<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\EventListener;

use Dunglas\ApiBundle\JsonLd\Response as JsonLdResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes data then builds the response object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResponderViewListener
{
    const FORMAT = 'jsonld';

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * In an API context, converts any data to a JSON-LD response.
     *
     * @param GetResponseForControllerResultEvent $event
     *
     * @return JsonLdResponse|mixed
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();

        if ($controllerResult instanceof Response) {
            return;
        }

        $request = $event->getRequest();

        $format = $request->attributes->get('_api_format');
        if (self::FORMAT !== $format) {
            return;
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
        $response = new JsonLdResponse(
            $resourceType ? $this->normalizer->normalize(
                $controllerResult, self::FORMAT, $resourceType->getNormalizationContext() + ['request_uri' => $request->getRequestUri()]
            ) : $controllerResult,
            $status
        );

        $event->setResponse($response);
    }
}
