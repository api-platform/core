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
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use ApiPlatform\State\StopwatchAwareInterface;
use ApiPlatform\State\StopwatchAwareTrait;
use ApiPlatform\Validator\Exception\ValidationException;
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

final class DeserializeProvider implements ProviderInterface, StopwatchAwareInterface
{
    use StopwatchAwareTrait;

    public function __construct(
        private readonly ?ProviderInterface $decorated,
        private readonly SerializerInterface $serializer,
        private readonly SerializerContextBuilderInterface $serializerContextBuilder,
        private ?TranslatorInterface $translator = null,
    ) {
        if (null === $this->translator) {
            $this->translator = new class implements TranslatorInterface, LocaleAwareInterface {
                use TranslatorTrait;
            };
            $this->translator->setLocale('en');
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
        } catch (PartialDenormalizationException $e) {
            if (!class_exists(ConstraintViolationList::class)) {
                throw $e;
            }

            $violations = new ConstraintViolationList();
            foreach ($e->getErrors() as $exception) {
                if (!$exception instanceof NotNormalizableValueException) {
                    continue;
                }
                $expectedTypes = $this->normalizeExpectedTypes($exception->getExpectedTypes());
                $message = (new Type($expectedTypes))->message;
                $parameters = [];
                if ($exception->canUseMessageForUser()) {
                    $parameters['hint'] = $exception->getMessage();
                }
                $violations->add(new ConstraintViolation($this->translator->trans($message, ['{{ type }}' => implode('|', $expectedTypes)], 'validators'), $message, $parameters, null, $exception->getPath(), null, null, (string) Type::INVALID_TYPE_ERROR));
            }
            if (0 !== \count($violations)) {
                throw new ValidationException($violations);
            }
        }

        $this->stopwatch?->stop('api_platform.provider.deserialize');

        return $data;
    }

    private function normalizeExpectedTypes(?array $expectedTypes = null): array
    {
        $normalizedTypes = [];

        foreach ($expectedTypes ?? [] as $expectedType) {
            $normalizedType = $expectedType;

            if (class_exists($expectedType) || interface_exists($expectedType)) {
                $classReflection = new \ReflectionClass($expectedType);
                $normalizedType = $classReflection->getShortName();
            }

            $normalizedTypes[] = $normalizedType;
        }

        return $normalizedTypes;
    }
}
