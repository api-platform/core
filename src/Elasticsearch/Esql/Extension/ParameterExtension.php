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

namespace ApiPlatform\Elasticsearch\Esql\Extension;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Elasticsearch\Esql\Filter\FilterInterface;
use ApiPlatform\Elasticsearch\Util\FieldDatatypeTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ParameterNotFound;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Applies parameter filters (QueryParameter) to the ES|QL query.
 *
 * The bridge between {@see \ApiPlatform\Metadata\Parameter} and
 * {@see FilterInterface}: resolves the
 * parameter's filter (instance or service id), converts the property name to
 * its Elasticsearch field name and rejects fields mapped as `nested`
 * (unsupported by ES|QL).
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class ParameterExtension implements CollectionExtensionInterface
{
    use FieldDatatypeTrait;

    public function __construct(
        private readonly ContainerInterface $filterLocator,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        ResourceClassResolverInterface $resourceClassResolver,
        private readonly ?NameConverterInterface $nameConverter = null,
    ) {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        foreach ($operation?->getParameters() ?? [] as $parameter) {
            if (null === ($value = $parameter->getValue()) || $value instanceof ParameterNotFound) {
                continue;
            }

            if (null === ($filterId = $parameter->getFilter())) {
                continue;
            }

            $filter = match (true) {
                $filterId instanceof FilterInterface => $filterId,
                \is_string($filterId) && $this->filterLocator->has($filterId) => $this->filterLocator->get($filterId),
                default => null,
            };

            if (!$filter instanceof FilterInterface) {
                continue;
            }

            $property = $parameter->getProperty() ?? $parameter->getKey();
            if (null !== $property && $this->isNestedField($resourceClass, $property)) {
                throw new InvalidArgumentException(\sprintf('The property "%s" of the resource "%s" is mapped as an Elasticsearch "nested" field, which is not supported by ES|QL. Use the Query DSL instead (see the "queryLanguage" state option).', $property, $resourceClass));
            }

            $esField = null === $property || null === $this->nameConverter ? $property : $this->nameConverter->normalize($property, $resourceClass, null, $context);

            $filter->apply($query, $resourceClass, $operation, [
                'parameter' => $parameter,
                'es_field' => $esField,
            ] + $context);
        }
    }
}
