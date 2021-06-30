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

namespace ApiPlatform\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Translation\ResourceTranslatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Generic item normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizer extends AbstractItemNormalizer
{
    private readonly LoggerInterface $logger;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, LoggerInterface $logger = null, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null, ResourceAccessCheckerInterface $resourceAccessChecker = null, array $defaultContext = [], ResourceTranslatorInterface $resourceTranslator = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $defaultContext, $resourceMetadataFactory, $resourceAccessChecker, $resourceTranslator);

        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotNormalizableValueException
     */
    public function denormalize(mixed $data, string $class, string $format = null, array $context = []): mixed
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['id']) && !isset($context[self::OBJECT_TO_POPULATE])) {
            if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            if (isset($context['resource_class'])) {
                $this->updateObjectToPopulate($data, $context);
            } else {
                // See https://github.com/api-platform/core/pull/2326 to understand this message.
                $this->logger->warning('The "resource_class" key is missing from the context.', [
                    'context' => $context,
                ]);
            }
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    private function updateObjectToPopulate(array $data, array &$context): void
    {
        try {
            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri((string) $data['id'], $context + ['fetch_data' => true]);
        } catch (InvalidArgumentException) {
            $operation = $this->resourceMetadataCollectionFactory->create($context['resource_class'])->getOperation();
            // todo: we could guess uri variables with the operation and the data instead of hardcoding id
            $iri = $this->iriConverter->getIriFromResource($context['resource_class'], UrlGeneratorInterface::ABS_PATH, $operation, ['uri_variables' => ['id' => $data['id']]]);

            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri($iri, ['fetch_data' => true]);
        }
    }
}
