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

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Model\DataProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Filter implements FilterInterface
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
     * @var DataProviderInterface
     */
    private $dataProvider;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $strategy;

    /**
     * @param string $name
     * @param string $strategy
     */
    public function __construct(
        DataProviderInterface $dataProvider,
        PropertyAccessorInterface $propertyAccessor,
        $name,
        $strategy = self::STRATEGY_EXACT
    ) {
        $this->dataProvider = $dataProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->name = $name;
        $this->strategy = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, $value)
    {
        $exact = self::STRATEGY_EXACT === $this->strategy;

        $metadata = $queryBuilder->getEntityManager()->getClassMetadata($resource->getEntityClass());
        $fieldNames = array_flip($metadata->getFieldNames());

        if (isset($fieldNames[$this->name])) {
            if ('id' === $this->name) {
                $value = $this->getFilterValueFromUrl($value);
            }

            $queryBuilder
                ->andWhere(sprintf('o.%1$s LIKE :%1$s', $this->name))
                ->setParameter($this->name, $exact ? $value : sprintf('%%%s%%', $value))
            ;
        } elseif ($metadata->isSingleValuedAssociation($this->name) || $metadata->isCollectionValuedAssociation($this->name)) {
            $value = $this->getFilterValueFromUrl($value);

            $queryBuilder
                ->join(sprintf('o.%s', $this->name), $this->name)
                ->andWhere(sprintf('%1$s.id = :%1$s', $this->name))
                ->setParameter($this->name, $exact ? $value : sprintf('%%%s%%', $value))
            ;
        }
    }

    /**
     * Gets the ID from an URI or a raw ID.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function getFilterValueFromUrl($value)
    {
        try {
            if ($item = $this->dataProvider->getItemFromIri($value)) {
                return $this->propertyAccessor->getValue($item, 'id');
            }
        } catch (\InvalidArgumentException $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }
}
