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

final class DeserializeProvider implements ProviderInterface
{
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

        if (!$operation->canDeserialize()) {
            return $data;
        }

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

        $method = $operation->getMethod();

        if (
            null !== $data
            && (
                'POST' === $method
                || 'PATCH' === $method
                || ('PUT' === $method && !($operation->getExtraProperties()['standard_put'] ?? true))
            )
        ) {
            $serializerContext[AbstractNormalizer::OBJECT_TO_POPULATE] = $data;
        }

        try {
            return $this->serializer->deserialize((string) $request->getContent(), $serializerContext['deserializer_type'] ?? $operation->getClass(), $format, $serializerContext);
        } catch (PartialDenormalizationException $e) {
            if (!class_exists(ConstraintViolationList::class)) {
                throw $e;
            }

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
                $violations->add(new ConstraintViolation($this->translator->trans($message, ['{{ type }}' => implode('|', $exception->getExpectedTypes() ?? [])], 'validators'), $message, $parameters, null, $exception->getPath(), null, null, (string) Type::INVALID_TYPE_ERROR));
            }
            if (0 !== \count($violations)) {
                throw new ValidationException($violations);
            }
        }

        return $data;
    }
}
