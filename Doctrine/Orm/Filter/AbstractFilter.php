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
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * Abstract class with helpers for easing the implementation of a filter.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * @var array|null
     */
    protected $properties;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry, array $properties = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->properties = $properties;
    }

    /**
     * Gets class metadata for the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return ClassMetadata
     */
    protected function getClassMetadata(ResourceInterface $resource)
    {
        $entityClass = $resource->getEntityClass();

        return $this
            ->managerRegistry
            ->getManagerForClass($entityClass)
            ->getClassMetadata($entityClass)
        ;
    }

    /**
     * Is the given property enabled?
     *
     * @param string $property
     *
     * @return bool
     */
    protected function isPropertyEnabled($property)
    {
        return null === $this->properties || array_key_exists($property, $this->properties);
    }

    /**
     * Extracts properties to filter from the request.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function extractProperties(Request $request)
    {
        return $request->query->all();
    }
}
