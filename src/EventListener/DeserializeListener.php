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

use ApiPlatform\Core\Api\FormatMatcher;
use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    private $formats = [];
    private $formatsProvider;
    private $formatMatcher;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder, /* FormatsProviderInterface */$formatsProvider)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
        if (\is_array($formatsProvider)) {
            @trigger_error('Using an array as formats provider is deprecated since API Platform 2.3 and will not be possible anymore in API Platform 3', E_USER_DEPRECATED);
            $this->formats = $formatsProvider;
        } else {
            if (!$formatsProvider instanceof FormatsProviderInterface) {
                throw new InvalidArgumentException(sprintf('The "$formatsProvider" argument is expected to be an implementation of the "%s" interface.', FormatsProviderInterface::class));
            }

            $this->formatsProvider = $formatsProvider;
        }
    }

    /**
     * Deserializes the data sent in the requested format.
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Deserializes the data sent in the requested format.
     */
    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }
        $method = $request->getMethod();

        if (
            'DELETE' === $method
            || $request->isMethodSafe(false)
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
            || (
                    '' === ($requestContent = $request->getContent())
                    && ('POST' === $method || 'PUT' === $method)
               )
        ) {
            return;
        }

        $context = $this->serializerContextBuilder->createFromRequest($request, false, $attributes);
        if (isset($context['input']) && \array_key_exists('class', $context['input']) && null === $context['input']['class']) {
            return;
        }

        // BC check to be removed in 3.0
        if (null !== $this->formatsProvider) {
            $this->formats = $this->formatsProvider->getFormatsFromAttributes($attributes);
        }
        $this->formatMatcher = new FormatMatcher($this->formats);
        $format = $this->getFormat($request);

        $data = $request->attributes->get('data');
        if (null !== $data) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $data;
        }

        $request->attributes->set(
            'data',
            $this->serializer->deserialize(
                $requestContent, $context['resource_class'], $format, $context
            )
        );
    }

    /**
     * Extracts the format from the Content-Type header and check that it is supported.
     *
     * @throws NotAcceptableHttpException
     */
    private function getFormat(Request $request): string
    {
        /**
         * @var string|null
         */
        $contentType = $request->headers->get('CONTENT_TYPE');
        if (null === $contentType) {
            throw new NotAcceptableHttpException('The "Content-Type" header must exist.');
        }

        $format = $this->formatMatcher->getFormat($contentType);
        if (null === $format || !isset($this->formats[$format])) {
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
