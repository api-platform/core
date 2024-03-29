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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\PropertyAwareFilterInterface;
use ApiPlatform\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Doctrine\Odm\PropertyHelperTrait as MongoDbOdmPropertyHelperTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 *
 * Abstract class for easing the implementation of a filter.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
abstract class AbstractFilter implements FilterInterface, PropertyAwareFilterInterface
{
    use MongoDbOdmPropertyHelperTrait;
    use PropertyHelperTrait;
    protected LoggerInterface $logger;

    public function __construct(protected ManagerRegistry $managerRegistry, ?LoggerInterface $logger = null, protected ?array $properties = null, protected ?NameConverterInterface $nameConverter = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        foreach ($context['filters'] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $aggregationBuilder, $resourceClass, $operation, $context);
        }
    }

    /**
     * Passes a property through the filter.
     */
    abstract protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void;

    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    protected function getProperties(): ?array
    {
        return $this->properties;
    }

    /**
     * @param string[] $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Determines whether the given property is enabled.
     */
    protected function isPropertyEnabled(string $property, string $resourceClass): bool
    {
        if (null === $this->properties) {
            // to ensure sanity, nested properties must still be explicitly enabled
            return !$this->isPropertyNested($property, $resourceClass);
        }

        return \array_key_exists($property, $this->properties);
    }

    protected function denormalizePropertyName(string|int $property): string
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return (string) $property;
        }

        return implode('.', array_map($this->nameConverter->denormalize(...), explode('.', (string) $property)));
    }

    protected function normalizePropertyName(string $property): string
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return $property;
        }

        return implode('.', array_map($this->nameConverter->normalize(...), explode('.', $property)));
    }
}
