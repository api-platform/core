<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Common methods for a ResourceController.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class AbstractResourceController extends Controller implements ResourceControllerInterface
{
    /**
     * @var Resource
     */
    protected $resource;

    /**
     * {@inheritdoc}
     */
    public function setResourceServiceId($resourceServiceId)
    {
        $this->resource = $this->container->get($resourceServiceId);
    }

    /**
     * Gets the Doctrine manager for this entity class.
     *
     * @return \Doctrine\Common\Persistence\ObjectManager|null
     */
    protected function getManager()
    {
        return $this->getDoctrine()->getManagerForClass($this->resource->getEntityClass());
    }

    /**
     * Gets the Doctrine repositories for this entity class.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository($this->resource->getEntityClass());
    }

    /**
     * Normalizes data using the Symfony Serializer.
     *
     * @param array|object $data
     *
     * @return array
     */
    protected function normalize($data)
    {
        return $this->get('serializer')->normalize($data, 'json-ld', $this->resource->getNormalizationContext());
    }

    /**
     * Finds an object of throws a 404 error.
     *
     * @param $id
     *
     * @return object
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function findOrThrowNotFound($id)
    {
        $object = $this->getRepository()->find($id);
        if (!$object) {
            throw $this->createNotFoundException();
        }

        return $object;
    }
}
