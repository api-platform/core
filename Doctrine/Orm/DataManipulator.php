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
use Dunglas\JsonLdApiBundle\Resource;
use Dunglas\JsonLdApiBundle\Resources;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
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
     * @param RouterInterface $routerInterface
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
                $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s', $filter['name']))
                    ->setParameter($filter['name'], $filter['exact'] ? $filter['value'] : sprintf('%%%s%%', $filter['value']))
                ;
            } elseif ($metadata->isSingleValuedAssociation($filter['name']) || $metadata->isCollectionValuedAssociation($filter['name'])) {
                try {
                    $object = $this->getObjectFromUri($filter)['value'];
                } catch (\InvalidArgumentException $e) {
                    // ignore this filter if the URI is invalid
                }
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
        $request = Request::create($uri);
        $context = (new RequestContext())->fromRequest($request);
        $baseContext = $this->router->getContext();

        try {
            $this->router->setContext($context);

            $parameters = $this->router->match($request->getPathInfo());
            if (
                !isset($parameters['_json_ld_resource']) ||
                !isset($parameters['id']) ||
                !($resource = $this->resources->getResourceForShortName($parameters['_json_ld_resource']))
            ) {
                throw new \InvalidArgumentException(sprintf('No resource associated with the URI "%s"', $uri));
            }

            $entityClass = $resource->getEntityClass();

            return $this->managerRegistry->getManagerForClass($entityClass)->getReference($entityClass, $parameters['id']);
        } finally {
            $this->router->setContext($baseContext);
        }
    }
}
