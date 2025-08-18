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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\PropertyAwareFilterInterface;
use ApiPlatform\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\PropertyHelperTrait as OrmPropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

abstract class AbstractFilter implements FilterInterface, PropertyAwareFilterInterface, ManagerRegistryAwareInterface
{
    use OrmPropertyHelperTrait;
    use PropertyHelperTrait;
    protected LoggerInterface $logger;

    public function __construct(
        protected ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        protected ?array $properties = null,
        protected ?NameConverterInterface $nameConverter = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        foreach ($context['filters'] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }
    }

    /**
     * Passes a property through the filter.
     *
     * @param class-string         $resourceClass
     * @param array<string, mixed> $context
     */
    abstract protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void;

    public function hasManagerRegistry(): bool
    {
        return $this->managerRegistry instanceof ManagerRegistry;
    }

    public function getManagerRegistry(): ManagerRegistry
    {
        if (!$this->hasManagerRegistry()) {
            throw new RuntimeException('ManagerRegistry must be initialized before accessing it.');
        }

        return $this->managerRegistry;
    }

    public function setManagerRegistry(ManagerRegistry $managerRegistry): void
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    /**
     * @param array<string, mixed> $properties
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
