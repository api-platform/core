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

use ApiPlatform\Doctrine\Common\Filter\IriSearchFilterTrait;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\Metadata\PropertiesFilterInterface;
use ApiPlatform\State\Provider\IriConverterParameterProvider;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class IriSearchFilter extends AbstractFilter implements OpenApiParameterFilterInterface, PropertiesFilterInterface, ParameterProviderFilterInterface
{
    use IriSearchFilterTrait;

    public function __construct(
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    /**
     * @throws MappingException
     */
    public function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        if (
            null === $value
            || !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $extraProperties = $operation?->getExtraProperties();
        $resource = $extraProperties['_value'] ?? null;

        if (!$resource) {
            $this->logger->warning(\sprintf('No resource found for property "%s".', $property));

            return;
        }

        $aggregationBuilder
            ->match()
            ->field($property)
            ->equals($resource->getId());
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(string $doctrineType): string
    {
        // TODO: remove constantes deprecations when doctrine/dbal:3 support is removed
        return match ($doctrineType) {
            MongoDbType::INT, MongoDbType::INTEGER => 'int',
            MongoDbType::BOOL, MongoDbType::BOOLEAN => 'bool',
            MongoDbType::DATE, MongoDbType::DATE_IMMUTABLE => \DateTimeInterface::class,
            MongoDbType::FLOAT => 'float',
            default => 'string',
        };
    }

    public static function getParameterProvider(): string
    {
        return IriConverterParameterProvider::class;
    }
}
