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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Api\FormatMatcher;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\SerializerContextBuilderInterface as LegacySerializerContextBuilderInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Updates the entity retrieved by the data provider with data contained in the request body.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DeserializeListener
{
    use OperationRequestInitiatorTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'deserialize';
    private SerializerInterface $serializer;
    private ?ProviderInterface $provider = null;

    public function __construct(
        ProviderInterface|SerializerInterface $serializer,
        private readonly LegacySerializerContextBuilderInterface|SerializerContextBuilderInterface|ResourceMetadataCollectionFactoryInterface|null $serializerContextBuilder = null,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null,
        private ?TranslatorInterface $translator = null
    ) {
        if ($serializer instanceof ProviderInterface) {
            $this->provider = $serializer;
        } else {
            trigger_deprecation('api-platform/core', '3.3', 'Use a "%s" as first argument in "%s" instead of "%s".', ProviderInterface::class, self::class, SerializerInterface::class);
            $this->serializer = $serializer;
        }

        if ($serializerContextBuilder instanceof ResourceMetadataCollectionFactoryInterface) {
            $resourceMetadataFactory = $serializerContextBuilder;
        } else {
            trigger_deprecation('api-platform/core', '3.3', 'Use a "%s" as second argument in "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, self::class, SerializerContextBuilderInterface::class);
        }

        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        if (null === $this->translator) {
            $this->translator = new class() implements TranslatorInterface, LocaleAwareInterface {
                use TranslatorTrait;
            };
            $this->translator->setLocale('en');
        }
    }

    /**
     * Deserializes the data sent in the requested format.
     *
     * @throws UnsupportedMediaTypeHttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $method = $request->getMethod();

        if (
            !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
        ) {
            return;
        }

        $operation = $this->initializeOperation($request);

        if ($operation && $this->provider) {
            if (null === $operation->canDeserialize() && $operation instanceof HttpOperation) {
                $operation = $operation->withDeserialize(\in_array($operation->getMethod(), ['POST', 'PUT', 'PATCH'], true));
            }

            if (!$operation->canDeserialize()) {
                return;
            }

            $data = $this->provider->provide($operation, $request->attributes->get('_api_uri_variables') ?? [], [
                'request' => $request,
                'uri_variables' => $request->attributes->get('_api_uri_variables') ?? [],
                'resource_class' => $operation->getClass(),
            ]);

            $request->attributes->set('data', $data);

            return;
        }

        // TODO: the code below needs to be removed in 4.x
        if (
            'DELETE' === $method
            || $request->isMethodSafe()
            || $request->attributes->get('_api_platform_disable_listeners')
        ) {
            return;
        }

        if ('api_platform.symfony.main_controller' === $operation?->getController()) {
            return;
        }

        if (!($operation?->canDeserialize() ?? true)) {
            return;
        }

        $context = $this->serializerContextBuilder->createFromRequest($request, false, $attributes);

        $format = $this->getFormat($request, $operation?->getInputFormats() ?? []);
        $data = $request->attributes->get('data');
        if (
            null !== $data
            && (
                'POST' === $method
                || 'PATCH' === $method
                || ('PUT' === $method && !($operation->getExtraProperties()['standard_put'] ?? false))
            )
        ) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $data;
        }
        try {
            $request->attributes->set(
                'data',
                $this->serializer->deserialize($request->getContent(), $context['resource_class'], $format, $context)
            );
        } catch (PartialDenormalizationException $e) {
            $violations = new ConstraintViolationList();
            foreach ($e->getErrors() as $exception) {
                if (!$exception instanceof NotNormalizableValueException) {
                    continue;
                }
                $message = (new Type($exception->getExpectedTypes() ?? []))->message;
                $parameters = [];
                if ($exception->canUseMessageForUser()) {
                    $parameters['hint'] = $exception->getMessage();
                }
                $violations->add(new ConstraintViolation($this->translator->trans($message, ['{{ type }}' => implode('|', $exception->getExpectedTypes() ?? [])], 'validators'), $message, $parameters, null, $exception->getPath(), null, null, Type::INVALID_TYPE_ERROR));
            }
            if (0 !== \count($violations)) {
                throw new ValidationException($violations);
            }
        }
    }

    /**
     * Extracts the format from the Content-Type header and check that it is supported.
     *
     * @throws UnsupportedMediaTypeHttpException
     */
    private function getFormat(Request $request, array $formats): string
    {
        /** @var ?string $contentType */
        $contentType = $request->headers->get('CONTENT_TYPE');
        if (null === $contentType || '' === $contentType) {
            throw new UnsupportedMediaTypeHttpException('The "Content-Type" header must exist.');
        }

        $formatMatcher = new FormatMatcher($formats);
        $format = $formatMatcher->getFormat($contentType);
        if (null === $format) {
            $supportedMimeTypes = [];
            foreach ($formats as $mimeTypes) {
                foreach ($mimeTypes as $mimeType) {
                    $supportedMimeTypes[] = $mimeType;
                }
            }

            throw new UnsupportedMediaTypeHttpException(sprintf('The content-type "%s" is not supported. Supported MIME types are "%s".', $contentType, implode('", "', $supportedMimeTypes)));
        }

        return $format;
    }
}
