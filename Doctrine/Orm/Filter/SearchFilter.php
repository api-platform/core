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
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
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
     * @var string Finds fields that are starting with the value.
     */
    const STRATEGY_START = 'start';
    /**
     * @var string Finds fields that are ending with the value.
     */
    const STRATEGY_END = 'end';
    /**
     * @var string Finds fields that are starting with the word.
     */
    const STRATEGY_WORD_START = 'word_start';

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

                $strategy = null !== $this->properties ? $this->properties[$property] : self::STRATEGY_EXACT;

                $this->addWhereByStrategy($strategy, $queryBuilder, $property, $value);
            } elseif ($metadata->isSingleValuedAssociation($property)
                || $metadata->isCollectionValuedAssociation($property)
            ) {
                $value = $this->getFilterValueFromUrl($value);

                $queryBuilder
                    ->join(sprintf('o.%s', $property), 'api_'.$property)
                    ->andWhere(sprintf('api_%1$s.id = :%1$s', $property))
                    ->setParameter($property, $value)
                ;
            }
        }
    }

    /**
     * Adds where clause according to the strategy.
     *
     * @param string       $strategy
     * @param QueryBuilder $queryBuilder
     * @param string       $property
     * @param string       $value
     *
     * @return string
     *
     * @throws InvalidArgumentException If strategy does not exist
     */
    private function addWhereByStrategy($strategy, QueryBuilder $queryBuilder, $property, $value)
    {
        switch ($strategy) {
            case self::STRATEGY_EXACT:
                return $queryBuilder
                    ->andWhere(sprintf('o.%1$s = :%1$s', $property))
                    ->setParameter($property, $value)
                ;

            case self::STRATEGY_PARTIAL:
                return $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s', $property))
                    ->setParameter($property, sprintf('%%%s%%', $value))
                ;

            case self::STRATEGY_START:
                return $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s', $property))
                    ->setParameter($property, sprintf('%s%%', $value))
                ;

            case self::STRATEGY_END:
                return $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s', $property))
                    ->setParameter($property, sprintf('%%%s', $value))
                ;

            case self::STRATEGY_WORD_START:
                return $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s_1 OR o.%1$s LIKE :%1$s_2', $property))
                    ->setParameter(sprintf('%s_1', $property), sprintf('%s%%', $value))
                    ->setParameter(sprintf('%s_2', $property), sprintf('%% %s%%', $value))
                ;
        }

        throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
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
