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

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Serializer\ConstraintViolationListNormalizerTrait;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ConstraintViolationListNormalizer as BaseConstraintViolationListNormalizer;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} to a Hydra error representation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ConstraintViolationListNormalizer extends BaseConstraintViolationListNormalizer
{
    use ConstraintViolationListNormalizerTrait;

    public const FORMAT = 'jsonld';

    private $urlGenerator;

    private $serializePayloadFields;

    protected $nameConverter;

    private $useSymfonyNormalizer;

    public function __construct(UrlGeneratorInterface $urlGenerator, array $serializePayloadFields = null, NameConverterInterface $nameConverter = null, bool $useSymfonyNormalizer = false)
    {
        parent::__construct([], $nameConverter);

        $this->nameConverter = $nameConverter;
        $this->serializePayloadFields = $serializePayloadFields;

        $this->urlGenerator = $urlGenerator;
        $this->useSymfonyNormalizer = $useSymfonyNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return static::FORMAT === $format && $data instanceof ConstraintViolationListInterface && parent::supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $normalized = [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList']),
            '@type' => 'ConstraintViolationList',
            'hydra:title' => $context['title'] ?? 'An error occurred',
        ];

        if ($this->useSymfonyNormalizer) {
            $parentNormalized = parent::normalize($object, $format, $context);
            $normalized['hydra:description'] = $parentNormalized['detail'];
            $normalized['violations'] = $parentNormalized['violations'];
            $this->addPayloadToViolations($object, $normalized['violations']);
        } else {
            [$messages, $violations] = $this->getMessagesAndViolations($object);
            $normalized['hydra:description'] = $messages ? implode("\n", $messages) : (string) $object;
            $normalized['violations'] = $violations;
        }

        return $normalized;
    }
}
