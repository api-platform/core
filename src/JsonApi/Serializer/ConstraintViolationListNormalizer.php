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

namespace ApiPlatform\Core\JsonApi\Serializer;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ConstraintViolationListNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonapi';

    private $nameConverter;

    public function __construct(
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        NameConverterInterface $nameConverter = null
    ) {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->nameConverter = $nameConverter;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $violations = [];
        foreach ($object as $violation) {
            $fieldName = $violation->getPropertyPath();

            $propertyMetadata = $this->propertyMetadataFactory
                ->create(
                    get_class($violation->getRoot()),
                    $fieldName
                );

            if ($this->nameConverter) {
                $fieldName = $this->nameConverter->normalize($fieldName);
            }

            $violationPath = sprintf(
                'data/attributes/%s',
                $fieldName
            );

            if (null !== $propertyMetadata->getType()->getClassName()) {
                $violationPath = sprintf(
                    'data/relationships/%s',
                    $fieldName
                );
            }

            $violations[] = [
                'detail' => $violation->getMessage(),
                'source' => [
                    'pointer' => $violationPath,
                ],
            ];
        }

        return ['errors' => $violations];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof ConstraintViolationListInterface;
    }
}
