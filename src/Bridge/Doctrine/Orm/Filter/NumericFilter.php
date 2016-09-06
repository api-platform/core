<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Filters the collection by numeric values.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
class NumericFilter extends AbstractFilter
{
    /**
     * Type of numeric in Doctrine.
     *
     * @see http://doctrine-orm.readthedocs.org/projects/doctrine-dbal/en/latest/reference/types.html
     */
    const DOCTRINE_NUMERIC_TYPES = [
        DBALType::BIGINT => true,
        DBALType::DECIMAL => true,
        DBALType::FLOAT => true,
        DBALType::INTEGER => true,
        DBALType::SMALLINT => true,
    ];

    private $requestStack;

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, LoggerInterface $logger = null, array $properties = null)
    {
        parent::__construct($managerRegistry, $logger, $properties);

        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     *
     * Filters collection by equality of numeric properties.
     *
     * For each property passed, if the resource does not have such property or if the value is not numeric
     * the property is ignored.
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $properties = $this->extractProperties($request);

        foreach ($properties as $property => $value) {
            if (
                !$this->isPropertyEnabled($property) ||
                !$this->isPropertyMapped($property, $resourceClass) ||
                !$this->isNumericField($property, $resourceClass)
            ) {
                continue;
            }

            if (!is_numeric($value)) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf('Invalid numeric value for "%s" property', $property)),
                ]);

                continue;
            }

            $alias = 'o';
            $field = $property;

            if ($this->isPropertyNested($property)) {
                list($alias, $field) = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator);
            }
            $valueParameter = $queryNameGenerator->generateParameterName($field);

            $queryBuilder
                ->andWhere(sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass) : array
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $unused) {
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isNumericField($property, $resourceClass)) {
                continue;
            }
            $propertyParts = $this->splitPropertyParts($property);
            $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

            $description[$property] = [
                'property' => $property,
                'type' => $metadata->getTypeOfField($propertyParts['field']),
                'required' => false,
            ];
        }

        return $description;
    }

    /**
     * Determines whether the given property refers to a numeric field.
     *
     * @param string $property
     * @param string $resourceClass
     *
     * @return bool
     */
    private function isNumericField(string $property, string $resourceClass) : bool
    {
        $propertyParts = $this->splitPropertyParts($property);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        return isset(self::DOCTRINE_NUMERIC_TYPES[$metadata->getTypeOfField($propertyParts['field'])]);
    }
}
