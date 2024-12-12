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
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\Metadata\PropertiesAwareInterface;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use ApiPlatform\State\Provider\IriConverterParameterProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class IriSearchFilter implements FilterInterface, ManagerRegistryAwareInterface, OpenApiParameterFilterInterface, PropertiesAwareInterface, ParameterProviderFilterInterface
{
    use FilterInterfaceTrait;

    public function __construct(
        private ?ManagerRegistry $managerRegistry = null,
        private readonly ?array $properties = null,
        private readonly ?NameConverterInterface $nameConverter = null,
    ) {
    }

    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            null === $value
            || !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $value = $context['parameter']->getValue();

        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName($property);

        $queryBuilder
            ->andWhere(\sprintf('%s.%s = :%s', $alias, $property, $parameterName))
            ->setParameter($parameterName, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getType(string $doctrineType): string
    {
        // TODO: remove this test when doctrine/dbal:3 support is removed
        if (\defined(Types::class.'::ARRAY') && Types::ARRAY === $doctrineType) {
            return 'array';
        }

        return match ($doctrineType) {
            Types::BIGINT, Types::INTEGER, Types::SMALLINT => 'int',
            Types::BOOLEAN => 'bool',
            Types::DATE_MUTABLE, Types::TIME_MUTABLE, Types::DATETIME_MUTABLE, Types::DATETIMETZ_MUTABLE, Types::DATE_IMMUTABLE, Types::TIME_IMMUTABLE, Types::DATETIME_IMMUTABLE, Types::DATETIMETZ_IMMUTABLE => \DateTimeInterface::class,
            Types::FLOAT => 'float',
            default => 'string',
        };
    }

    public static function getParameterProvider(): string
    {
        return IriConverterParameterProvider::class;
    }

    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array|null
    {
        return new OpenApiParameter(name: $parameter->getKey().'[]', in: 'query', style: 'deepObject', explode: true);
    }
}
