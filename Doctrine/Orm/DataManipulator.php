<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Dunglas\JsonLdApiBundle\Model\DataManipulatorInterface;
use Dunglas\JsonLdApiBundle\JsonLd\Resource;
use Dunglas\JsonLdApiBundle\JsonLd\Resources;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Manipulates data through Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataManipulator implements DataManipulatorInterface
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;
    /**
     * @var Resources
     */
    private $resources;
    /**
     * @var int
     */
    private $defaultByPage;
    /**
     * @var string
     */
    private $defaultOrder;

    /**
     * @param RouterInterface $router
     * @param ManagerRegistry $managerRegistry
     * @param Resources       $resources
     * @param int             $defaultByPage
     * @param string|null     $defaultOrder
     */
    public function __construct(
        RouterInterface $router,
        ManagerRegistry $managerRegistry,
        Resources $resources,
        $defaultByPage,
        $defaultOrder
    ) {
        $this->router = $router;
        $this->managerRegistry = $managerRegistry;
        $this->resources = $resources;
        $this->defaultByPage = $defaultByPage;
        $this->defaultOrder = $defaultOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(Resource $resource, $page, array $filters, $byPage = null, $order = null)
    {
        if (!$byPage) {
            $byPage = $this->defaultByPage;
        }

        if (!$order) {
            $order = $this->defaultOrder;
        }

        $manager = $this->managerRegistry->getManagerForClass($resource->getEntityClass());
        $repository = $manager->getRepository($resource->getEntityClass());
        if (count($filters)) {
            $metadata = $manager->getClassMetadata($resource->getEntityClass());
            $fieldNames = array_flip($metadata->getFieldNames());
        }

        /*
         * @var \Doctrine\ORM\QueryBuilder
         */
        $queryBuilder = $repository
            ->createQueryBuilder('o')
            ->setFirstResult(($page - 1) * $byPage)
            ->setMaxResults($byPage)
        ;

        foreach ($filters as $filter) {
            if (isset($fieldNames[$filter['name']])) {
                if ('id' === $filter['name']) {
                    $filter['value'] = $this->getFilterValueFromUrl($filter['value']);
                }

                $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s', $filter['name']))
                    ->setParameter($filter['name'], $filter['exact'] ? $filter['value'] : sprintf('%%%s%%', $filter['value']))
                ;
            } elseif ($metadata->isSingleValuedAssociation($filter['name']) || $metadata->isCollectionValuedAssociation($filter['name'])) {
                $value = $this->getFilterValueFromUrl($filter['value']);

                $queryBuilder
                    ->join(sprintf('o.%s', $filter['name']), $filter['name'])
                    ->andWhere(sprintf('%1$s.id = :%1$s', $filter['name']))
                    ->setParameter($filter['name'], $filter['exact'] ? $value : sprintf('%%%s%%', $value))
                ;
            }
        }

        if ($order) {
            $queryBuilder->addOrderBy('o.id', $order);
        }

        return new Paginator($queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function getUriFromObject($object, $type)
    {
        $resource = $this->resources->getResourceForEntity($type);
        if (!$resource) {
            throw new \InvalidArgumentException(sprintf('No resource associated with the type "%s"', $type));
        }

        return $this->router->generate($resource->getElementRoute(), ['id' => $object->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectFromUri($uri)
    {
        $parameters = $this->router->match($uri);
        if (
            !isset($parameters['_json_ld_resource']) ||
            !isset($parameters['id']) ||
            !($resource = $this->resources->getResourceForShortName($parameters['_json_ld_resource']))
        ) {
            throw new \InvalidArgumentException(sprintf('No resource associated with the URI "%s"', $uri));
        }

        $entityClass = $resource->getEntityClass();

        return $this->managerRegistry->getManagerForClass($entityClass)->getReference($entityClass, $parameters['id']);
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
            if ($object = $this->getObjectFromUri($value)) {
                return $object->getId();
            }
        } catch (ExceptionInterface $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }
}
