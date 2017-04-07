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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Order the collection by given properties.
 *
 * The ordering is done in the same sequence as they are specified in the query,
 * and for each property a direction value can be specified.
 *
 * For each property passed, if the resource does not have such property or if the
 * direction value is different from "asc" or "desc" (case insensitive), the property
 * is ignored.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class OrderFilter extends AbstractFilter
{
    /**
     * @var string Keyword used to retrieve the value
     */
    protected $orderParameterName;

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, string $orderParameterName, LoggerInterface $logger = null, array $properties = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties);

        $this->orderParameterName = $orderParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $defaultDirection) {
            if (!$this->isPropertyMapped($property, $resourceClass)) {
                continue;
            }

            $description[sprintf('%s[%s]', $this->orderParameterName, $property)] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
            ];
        }

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $direction, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (!$this->isPropertyEnabled($property) || !$this->isPropertyMapped($property, $resourceClass)) {
            return;
        }

        if (empty($direction) && isset($this->properties[$property])) {
            // fallback to default direction
            $direction = $this->properties[$property];
        }

        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            return;
        }

        $alias = 'o';
        $field = $property;

        if ($this->isPropertyNested($property)) {
            list($alias, $field) = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator);
        }

        $queryBuilder->addOrderBy(sprintf('%s.%s', $alias, $field), $direction);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractProperties(Request $request): array
    {
        return $request->query->get($this->orderParameterName, []);
    }
}
