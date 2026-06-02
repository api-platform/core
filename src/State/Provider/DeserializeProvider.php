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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\DenormalizationViolationFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use ApiPlatform\State\StopwatchAwareInterface;
use ApiPlatform\State\StopwatchAwareTrait;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeserializeProvider implements ProviderInterface, StopwatchAwareInterface
{
    use StopwatchAwareTrait;

    public function __construct(
        private readonly ?ProviderInterface $decorated,
        private readonly SerializerInterface $serializer,
        private readonly SerializerContextBuilderInterface $serializerContextBuilder,
        ?TranslatorInterface $translator = null,
        private readonly ?DenormalizationViolationFactoryInterface $violationFactory = null,
    ) {
        if (null !== $translator) {
            trigger_deprecation('api-platform/core', '4.4', 'Passing a "%s" to "%s" is deprecated and will be removed in 5.0. Translation is now handled by "%s".', TranslatorInterface::class, self::class, DenormalizationViolationFactoryInterface::class);
        }
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // We need request content
        if (!$operation instanceof HttpOperation || !($request = $context['request'] ?? null)) {
            return $this->decorated?->provide($operation, $uriVariables, $context);
        }

        $data = $this->decorated ? $this->decorated->provide($operation, $uriVariables, $context) : $request->attributes->get('data');

        if (!$operation->canDeserialize() || $context['request']->attributes->has('deserialized')) {
            return $data;
        }

        $this->stopwatch?->start('api_platform.provider.deserialize');

        $contentType = $request->headers->get('CONTENT_TYPE');
        if (null === $contentType || '' === $contentType) {
            throw new UnsupportedMediaTypeHttpException('The "Content-Type" header must exist.');
        }

        $serializerContext = $this->serializerContextBuilder->createFromRequest($request, false, [
            'resource_class' => $operation->getClass(),
            'operation' => $operation,
        ]);

        $serializerContext['uri_variables'] = $uriVariables;

        if (!$format = $request->attributes->get('input_format') ?? null) {
            throw new UnsupportedMediaTypeHttpException('Format not supported.');
        }

        if (null === ($serializerContext[SerializerContextBuilderInterface::ASSIGN_OBJECT_TO_POPULATE] ?? null)) {
            $method = $operation->getMethod();
            $assignObjectToPopulate = 'POST' === $method
                || 'PATCH' === $method
                || ('PUT' === $method && !($operation->getExtraProperties()['standard_put'] ?? true));

            if ($assignObjectToPopulate) {
                $serializerContext[SerializerContextBuilderInterface::ASSIGN_OBJECT_TO_POPULATE] = true;
                trigger_deprecation('api-platform/core', '5.0', 'To assign an object to populate you should set "%s" in your denormalizationContext, not defining it is deprecated.', SerializerContextBuilderInterface::ASSIGN_OBJECT_TO_POPULATE);
            }
        }

        if (null !== $data && ($serializerContext[SerializerContextBuilderInterface::ASSIGN_OBJECT_TO_POPULATE] ?? false)) {
            $serializerContext[AbstractNormalizer::OBJECT_TO_POPULATE] = $data;
        }

        unset($serializerContext[SerializerContextBuilderInterface::ASSIGN_OBJECT_TO_POPULATE]);

        try {
            $data = $this->serializer->deserialize((string) $request->getContent(), $serializerContext['deserializer_type'] ?? $operation->getClass(), $format, $serializerContext);
        } catch (PartialDenormalizationException|NotNormalizableValueException $e) {
            $this->violationFactory?->handle($e, $operation);

            throw $e;
        }

        $this->stopwatch?->stop('api_platform.provider.deserialize');

        $request->attributes->set('data', $data);

        return $data;
    }
}
