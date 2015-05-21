<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\IriConverterInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SearchFilter implements FilterInterface
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
     * @var ManagerRegistry
     */
    private $managerRegistry;
    /**
     * @var IriConverterInterface
     */
    private $iriConverter;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var null|array
     */
    private $properties;

    /**
     * @param ManagerRegistry           $managerRegistry
     * @param IriConverterInterface     $iriConverter
     * @param PropertyAccessorInterface $propertyAccessor
     * @param null|array                $properties       Null to allow filtering on all properties with the exact strategy or a map of property name with strategy.
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        IriConverterInterface $iriConverter,
        PropertyAccessorInterface $propertyAccessor,
        array $properties = null
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor;
        $this->properties = $properties;
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
            $description[$associationName] = [
                'property' => $associationName,
                'type' => 'iri',
                'required' => false,
                'strategy' => self::STRATEGY_EXACT,
            ];
        }

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        $metadata = $this->getClassMetadata($resource);
        $fieldNames = array_flip($metadata->getFieldNames());

        foreach ($request->query->all() as $filter => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (null === $this->properties || isset($this->properties[$filter])) {
                $partial = null !== $this->properties && self::STRATEGY_PARTIAL === $this->properties[$filter];

                if (isset($fieldNames[$filter])) {
                    if ('id' === $filter) {
                        $value = $this->getFilterValueFromUrl($value);
                    }

                    $queryBuilder
                        ->andWhere(sprintf('o.%1$s LIKE :%1$s', $filter))
                        ->setParameter($filter, $partial ? sprintf('%%%s%%', $value) : $value)
                    ;
                } elseif (
                    $metadata->isSingleValuedAssociation($filter) || $metadata->isCollectionValuedAssociation($filter)
                ) {
                    $value = $this->getFilterValueFromUrl($value);

                    $queryBuilder
                        ->join(sprintf('o.%s', $filter), $filter)
                        ->andWhere(sprintf('%1$s.id = :%1$s', $filter))
                        ->setParameter($filter, $partial ? sprintf('%%%s%%', $value) : $value)
                    ;
                }
            }
        }
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

    /**
     * Gets class metadata for the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    private function getClassMetadata(ResourceInterface $resource)
    {
        $entityClass = $resource->getEntityClass();

        return $this
            ->managerRegistry
            ->getManagerForClass($entityClass)
            ->getClassMetadata($entityClass)
        ;
    }
}
