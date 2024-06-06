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

namespace ApiPlatform\Hydra\Serializer;

use ApiPlatform\Api\UrlGeneratorInterface as LegacyUrlGeneratorInterface;
use ApiPlatform\JsonLd\Serializer\HydraPrefixTrait;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\AbstractConstraintViolationListNormalizer;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} to a Hydra error representation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ConstraintViolationListNormalizer extends AbstractConstraintViolationListNormalizer
{
    use HydraPrefixTrait;
    public const FORMAT = 'jsonld';

    public function __construct(private readonly UrlGeneratorInterface|LegacyUrlGeneratorInterface $urlGenerator, ?array $serializePayloadFields = null, ?NameConverterInterface $nameConverter = null)
    {
        parent::__construct($serializePayloadFields, $nameConverter);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        [$messages, $violations] = $this->getMessagesAndViolations($object);

        // TODO: in api platform 4 this will be the default, as right now we serialize a ValidationException instead of a ConstraintViolationList
        if ($context['rfc_7807_compliant_errors'] ?? false) {
            return $violations;
        }

        $hydraPrefix = $this->getHydraPrefix($context);

        return [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList']),
            '@type' => 'ConstraintViolationList',
            $hydraPrefix.'title' => $context['title'] ?? 'An error occurred',
            $hydraPrefix.'description' => $messages ? implode("\n", $messages) : (string) $object,
            'violations' => $violations,
        ];
    }
}
