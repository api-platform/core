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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CRUD operations for a JSON-LD/Hydra API.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceController extends Controller
{
    /**
     * Gets the Resource associated with the current Request.
     * Must be called before manipulating the resource.
     *
     * @param Request $request
     *
     * @return Resource
     *
     * @throws \InvalidArgumentException
     */
    protected function getResource(Request $request)
    {
        if (!$request->attributes->has('_json_ld_api_resource')) {
            throw new \InvalidArgumentException('The current request doesn\'t have an associated resource.');
        }

        $serviceId = $request->attributes->get('_json_ld_api_resource');
        if (!$this->has($serviceId)) {
            throw new \InvalidArgumentException(sprintf('The service "%s" isn\'t registered.', $serviceId));
        }

        return $this->get($serviceId);
    }

    /**
     * Gets the Doctrine manager for this entity class.
     *
     * @param Resource $resource
     *
     * @return \Doctrine\Common\Persistence\ObjectManager|null
     */
    protected function getManager(Resource $resource)
    {
        return $this->getDoctrine()->getManagerForClass($resource->getEntityClass());
    }

    /**
     * Gets the Doctrine repositories for this entity class.
     *
     * @param Resource $resource
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository(Resource $resource)
    {
        return $this->getManager($resource)->getRepository($resource->getEntityClass());
    }

    /**
     * Normalizes data using the Symfony Serializer.
     *
     * @param Resource     $resource
     * @param array|object $data
     *
     * @return array
     */
    protected function normalize(Resource $resource, $data)
    {
        return $this->get('serializer')->normalize($data, 'json-ld', $resource->getNormalizationContext());
    }

    /**
     * Finds an object of throws a 404 error.
     *
     * @param Resource   $resource
     * @param string|int $id
     *
     * @return object
     *
     * @throws NotFoundHttpException
     */
    protected function findOrThrowNotFound(Resource $resource, $id)
    {
        $object = $this->getRepository($resource)->find($id);
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
     *
     * @throws \InvalidArgumentException
     */
    public function cgetAction(Request $request)
    {
        $resource = $this->getResource($request);

        return new JsonLdResponse($this->normalize($resource, $this->getRepository($resource)->findAll()));
    }

    /**
     * Adds an element to the collection.
     *
     * @param Request $request
     *
     * @return JsonLdResponse
     *
     * @throws \InvalidArgumentException
     */
    public function postAction(Request $request)
    {
        $resource = $this->getResource($request);
        $object = $this->get('serializer')->deserialize(
            $request->getContent(),
            $resource->getEntityClass(),
            'json-ld',
            $resource->getDenormalizationContext()
        );

        $violations = $this->get('validator')->validate($object, null, $resource->getValidationGroups());
        if (0 === count($violations)) {
            // Validation succeed
            $manager = $this->getManager($resource);

            $manager->persist($object);
            $manager->flush();

            return new JsonLdResponse($this->normalize($resource, $object), 201);
        }

        return new JsonLdResponse($violations, 400);
    }

    /**
     * Gets an element of the collection.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonLdResponse
     *
     * @throws NotFoundHttpException
     * @throws \InvalidArgumentException
     */
    public function getAction(Request $request, $id)
    {
        $resource = $this->getResource($request);
        $object = $this->findOrThrowNotFound($resource, $id);

        return new JsonLdResponse($this->normalize($resource, $object));
    }

    /**
     * Replaces an element of the collection.
     *
     * @param Request $request
     * @param string  $id
     *
     * @return JsonLdResponse
     *
     * @throws NotFoundHttpException
     * @throws \InvalidArgumentException
     */
    public function putAction(Request $request, $id)
    {
        $resource = $this->getResource($request);
        $object = $this->findOrThrowNotFound($resource, $id);

        $context = $resource->getDenormalizationContext();
        $context['object_to_populate'] = $object;

        $object = $this->get('serializer')->deserialize(
            $request->getContent(),
            $resource->getEntityClass(),
            'json-ld',
            $context
        );

        $violations = $this->get('validator')->validate($object, null, $resource->getValidationGroups());
        if (0 === count($violations)) {
            // Validation succeed
            $this->getManager($resource)->flush();

            return new JsonLdResponse($this->normalize($resource, $object), 202);
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
     * @throws NotFoundHttpException
     * @throws \InvalidArgumentException
     */
    public function deleteAction(Request $request, $id)
    {
        $resource = $this->getResource($request);
        $object = $this->findOrThrowNotFound($resource, $id);

        $manager = $this->getManager($resource);
        $manager->remove($object);
        $manager->flush();

        return new JsonLdResponse(null, 204);
    }
}
