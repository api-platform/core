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
        if (!$event->isMasterRequest()) {
            return;
        }

        $controllerResult = $event->getControllerResult();

        if ($controllerResult instanceof Response) {
            return $controllerResult;
        }

        $request = $event->getRequest();

        $resourceType = $request->attributes->get('_resource_type');
        $format = $request->attributes->get('_api_format');
        if (!$resourceType || self::FORMAT !== $format) {
            return $controllerResult;
        }

        switch ($request->getMethod()) {
            case 'POST':
                $status = 201;
                break;

            case 'DELETE':
                $status = 204;
                break;

            default:
                $status = 200;
                break;
        }

        $response = new JsonLdResponse(
            $this->normalizer->normalize(
                $controllerResult, self::FORMAT, $resourceType->getNormalizationContext() + ['request_uri' => $request->getRequestUri()]
            ),
            $status
        );

        $event->setResponse($response);
    }
}
