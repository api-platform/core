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

use Dunglas\JsonLdApiBundle\Resource;
use Dunglas\JsonLdApiBundle\Response\JsonLdResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * CRUD operations for a JSON-LD/Hydra API.
 *
 * A Request class is injected to all actions method to ease the extension of this class.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceController extends Controller implements ResourceControllerInterface
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

    /**
     * Gets the collection.
     *
     * @param Request $request
     *
     * @return JsonLdResponse
     */
    public function cgetAction(Request $request)
    {
        return new JsonLdResponse($this->normalize($this->getRepository()->findAll()));
    }

    /**
     * Adds an element to the collection.
     *
     * @param Request $request
     *
     * @return JsonLdResponse
     */
    public function postAction(Request $request)
    {
        $object = $this->get('serializer')->deserialize(
            $request->getContent(),
            $this->resource->getEntityClass(),
            'json-ld',
            $this->resource->getDenormalizationContext()
        );

        @$violations = $this->get('validator')->validate($object, null, $this->resource->getValidationGroups());
        if (0 === count($violations)) {
            // Validation succeed
            $this->getManager()->persist($object);
            $this->getManager()->flush();

            return new JsonLdResponse($this->normalize($object), 201);
        }

        return new JsonLdResponse($violations, 400);
    }

    /**
     * Gets an element of the collection.
     *
     * @param Request $request
     * @param int $id
     *
     * @return JsonLdResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getAction(Request $request, $id)
    {
        $object = $this->findOrThrowNotFound($id);

        return new JsonLdResponse($this->normalize($object));
    }

    /**
     * Replaces an element of the collection.
     *
     * @param Request $request
     * @param string  $id
     *
     * @return JsonLdResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function putAction(Request $request, $id)
    {
        $object = $this->findOrThrowNotFound($id);

        $context = $this->resource->getDenormalizationContext();
        $context['object_to_populate'] = $object;

        $object = $this->get('serializer')->deserialize(
            $request->getContent(),
            $this->resource->getEntityClass(),
            'json-ld',
            $context
        );

        $violations = $this->get('validator')->validate($object, null, $this->resource->getValidationGroups());
        if (0 === count($violations)) {
            // Validation succeed
            $this->getManager()->flush();

            return new JsonLdResponse($this->normalize($object), 202);
        }

        return new JsonLdResponse($violations, 400);
    }

    /**
     * Deletes an element of the collection.
     *
     * @param Request $request
     * @param string  $id
     *
     * @return JsonLdResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction(Request $request, $id)
    {
        $object = $this->findOrThrowNotFound($id);

        $manager = $this->getManager();
        $manager->remove($object);
        $manager->flush();

        return new JsonLdResponse(null, 204);
    }
}
