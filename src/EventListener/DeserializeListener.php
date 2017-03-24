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
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
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
    private $formats;

    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder, array $formats)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->formats = $formats;
    }

    /**
     * Deserializes the data sent in the requested format.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($request->isMethodSafe(false) || $request->isMethod(Request::METHOD_DELETE)) {
            return;
        }

        try {
            $attributes = RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
            return;
        }

        $format = $this->getFormat($request);
        $context = $this->serializerContextBuilder->createFromRequest($request, false, $attributes);

        $data = $request->attributes->get('data');
        if (null !== $data) {
            $context['object_to_populate'] = $data;
        }

        $request->attributes->set(
            'data',
            $this->serializer->deserialize(
                $request->getContent(), $attributes['resource_class'], $format, $context
            )
        );
    }

    /**
     * Extracts the format from the Content-Type header and check that it is supported.
     *
     * @param Request $request
     *
     * @throws NotAcceptableHttpException
     *
     * @return string
     */
    private function getFormat(Request $request): string
    {
        $contentType = $request->headers->get('CONTENT_TYPE');
        if (null === $contentType) {
            throw new NotAcceptableHttpException('The "Content-Type" header must exist.');
        }

        $format = $request->getFormat($contentType);
        if (!isset($this->formats[$format])) {
            $supportedMimeTypes = [];
            foreach ($this->formats as $mimeTypes) {
                foreach ($mimeTypes as $mimeType) {
                    $supportedMimeTypes[] = $mimeType;
                }
            }

            throw new NotAcceptableHttpException(sprintf(
                'The content-type "%s" is not supported. Supported MIME types are "%s".',
                $contentType,
                implode('", "', $supportedMimeTypes)
            ));
        }

        return $format;
    }
}
