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
abstract class AbstractFilter implements FilterInterface
{
    use MongoDbOdmPropertyHelperTrait;
    use PropertyHelperTrait;

    /** @var ManagerRegistry */
    protected $managerRegistry;
    /** @var LoggerInterface */
    protected $logger;
    /** @var array|null */
    protected $properties;
    /** @var NameConverterInterface|null */
    protected $nameConverter;

    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger ?? new NullLogger();
        $this->properties = $properties;
        $this->nameConverter = $nameConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, Operation $operation = null, array &$context = [])
    {
        foreach ($context['filters'] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $aggregationBuilder, $resourceClass, $operation, $context);
        }
    }

    /**
     * Passes a property through the filter.
     *
     * @param mixed $value
     */
    abstract protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, Operation $operation = null, array &$context = []);

    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    protected function getProperties(): ?array
    {
        return $this->properties;
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

    protected function denormalizePropertyName($property): string
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return $property;
        }

        return implode('.', array_map([$this->nameConverter, 'denormalize'], explode('.', (string) $property)));
    }

    protected function normalizePropertyName($property): string
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return $property;
        }

        return implode('.', array_map([$this->nameConverter, 'normalize'], explode('.', (string) $property)));
    }
}
