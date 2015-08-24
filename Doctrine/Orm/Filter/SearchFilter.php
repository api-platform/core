<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\IriConverterInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Filter the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SearchFilter extends AbstractFilter
{
    /**
     * @var string Exact matching.
     */
    const STRATEGY_EXACT = 'exact';
    /**
     * @var string The value must be contained in the field.
     */
    const STRATEGY_PARTIAL = 'partial';

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param ManagerRegistry           $managerRegistry
     * @param RequestStack              $requestStack
     * @param IriConverterInterface     $iriConverter
     * @param PropertyAccessorInterface $propertyAccessor
     * @param null|array                $properties       Null to allow filtering on all properties with the exact strategy or a map of property name with strategy.
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        IriConverterInterface $iriConverter,
        PropertyAccessorInterface $propertyAccessor,
        array $properties = null
    ) {
        parent::__construct($managerRegistry, $properties);

        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $metadata = $this->getClassMetadata($resource);
        $fieldNames = array_flip($metadata->getFieldNames());

        foreach ($this->extractProperties($request) as $property => $value) {
            if (null === $value || !$this->isPropertyEnabled($property)) {
                continue;
            }

            if (isset($fieldNames[$property])) {
                if (!is_string($value)) {
                    continue;
                }

                $partial = null !== $this->properties && self::STRATEGY_PARTIAL === $this->properties[$property];

                if ('id' === $property) {
                    $value = $this->getFilterValueFromUrl($value);
                }

                $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s', $property))
                    ->setParameter($property, $partial ? sprintf('%%%s%%', $value) : $value)
                ;
            } elseif ($metadata->isSingleValuedAssociation($property)) {
                if (!is_string($value)) {
                    continue;
                }

                $value = $this->getFilterValueFromUrl($value);

                $queryBuilder
                    ->join(sprintf('o.%s', $property), 'api_'.$property)
                    ->andWhere(sprintf('api_%1$s.id = :%1$s', $property))
                    ->setParameter($property, $value)
                ;
            } elseif ($metadata->isCollectionValuedAssociation($property)) {
                $values = $value;
                if (!is_array($values)) {
                    $values = [$value];
                }
                foreach ($values as $k => $v) {
                    if (!is_int($k) || !is_string($v)) {
                        unset($values[$k]);
                    }
                }

                if (empty($values)) {
                    continue;
                }

                $values = array_map([$this, 'getFilterValueFromUrl'], $values);

                $queryBuilder
                    ->join(sprintf('o.%s', $property), 'api_'.$property)
                    ->andWhere(sprintf('api_%1$s.id IN (:%1$s)', $property))
                    ->setParameter($property, $values)
                ;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(ResourceInterface $resource)
    {
        $description = [];
        $metadata = $this->getClassMetadata($resource);

        foreach ($metadata->getFieldNames() as $fieldName) {
            $found = isset($this->properties[$fieldName]);
            if ($found || null === $this->properties) {
                $description[$fieldName] = [
                    'property' => $fieldName,
                    'type' => $metadata->getTypeOfField($fieldName),
                    'required' => false,
                    'strategy' => $found ? $this->properties[$fieldName] : self::STRATEGY_EXACT,
                ];
            }
        }

        foreach ($metadata->getAssociationNames() as $associationName) {
            if ($this->isPropertyEnabled($associationName)) {
                $paramName = $associationName;
                if ($metadata->isCollectionValuedAssociation($associationName)) {
                    $paramName .= '[]';
                }
                $description[$paramName] = [
                    'property' => $associationName,
                    'type' => 'iri',
                    'required' => false,
                    'strategy' => self::STRATEGY_EXACT,
                ];
            }
        }

        return $description;
    }

    /**
     * Gets the ID from an URI or a raw ID.
     *
     * @param string $value
     *
     * @return string
     */
    private function getFilterValueFromUrl($value)
    {
        try {
            if ($item = $this->iriConverter->getItemFromIri($value)) {
                return $this->propertyAccessor->getValue($item, 'id');
            }
        } catch (\InvalidArgumentException $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }
}
