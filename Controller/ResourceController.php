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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Dunglas\JsonLdApiBundle\Event\Events;
use Dunglas\JsonLdApiBundle\Event\ObjectEvent;
use Dunglas\JsonLdApiBundle\JsonLd\Resource;
use Dunglas\JsonLdApiBundle\Exception\DeserializationException;
use Dunglas\JsonLdApiBundle\Response\JsonLdResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Serializer\Exception\Exception;

/**
 * CRUD operations for a JSON-LD/Hydra API.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceController extends Controller
{
    /**
     * @var Resource
     */
    private $resource;

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
        if ($this->resource) {
            return $this->resource;
        }

        if (!$request->attributes->has('_json_ld_resource')) {
            throw new \InvalidArgumentException('The current request doesn\'t have an associated resource.');
        }

        $shortName = $request->attributes->get('_json_ld_resource');
        if (!($this->resource = $this->get('dunglas_json_ld_api.resources')->getResourceForShortName($shortName))) {
            throw new \InvalidArgumentException(sprintf('The resource "%s" cannot be found.', $shortName));
        }

        return $this->resource;
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
     * @return \Doctrine\ORM\EntityRepository
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
     * @param int          $status
     * @param array        $headers
     *
     * @return JsonLdResponse
     */
    protected function getSuccessResponse(Resource $resource, $data, $status = 200, array $headers = [])
    {
        return new JsonLdResponse(
            $this->get('serializer')->normalize($data, 'json-ld', $resource->getNormalizationContext()),
            $status,
            $headers
        );
    }

    /**
     * @param ConstraintViolationListInterface $violations
     *
     * @return JsonLdResponse
     */
    protected function getErrorResponse(ConstraintViolationListInterface $violations)
    {
        return new JsonLdResponse($this->get('serializer')->normalize($violations, 'hydra-error'), 400);
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
     * Gets collection data.
     *
     * @param Resource $resource
     * @param Request  $request
     *
     * @return Paginator
     */
    protected function getCollectionData(Resource $resource, Request $request)
    {
        $page = (int) $request->get('page', 1);

        $filters = [];
        foreach ($resource->getFilters() as $resourceFilter) {
            if (null !== $value = $request->get($resourceFilter['name'])) {
                $resourceFilter['value'] = $value;
                $filters[] = $resourceFilter;
            }
        }

        $itemsPerPage = $this->container->getParameter('dunglas_json_ld_api.default.items_per_page');
        $order = $this->container->getParameter('dunglas_json_ld_api.default.order');

        return $resource->getManager()->getCollection($page, $filters, $itemsPerPage, $order);
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
        $data = $this->getCollectionData($resource, $request);

        $this->get('event_dispatcher')->dispatch(Events::RETRIEVE_LIST, new ObjectEvent($resource, $data));

        return $this->getSuccessResponse($resource, $data);
    }

    /**
     * Adds an element to the collection.
     *
     * @param Request $request
     *
     * @return JsonLdResponse
     *
     * @throws DeserializationException
     */
    public function postAction(Request $request)
    {
        $resource = $this->getResource($request);
        try {
            $object = $this->get('serializer')->deserialize(
                $request->getContent(),
                $resource->getEntityClass(),
                'json-ld',
                $resource->getDenormalizationContext()
            );
        } catch (Exception $e) {
            throw new DeserializationException($e->getMessage(), $e->getCode(), $e);
        }

        $this->get('event_dispatcher')->dispatch(Events::PRE_CREATE_VALIDATION, new ObjectEvent($resource, $object));

        $violations = $this->get('validator')->validate($object, null, $resource->getValidationGroups());
        if (0 === count($violations)) {
            // Validation succeed
            $this->get('event_dispatcher')->dispatch(Events::PRE_CREATE, new ObjectEvent($resource, $object));

            return $this->getSuccessResponse($resource, $object, 201);
        }

        return $this->getErrorResponse($violations);
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

        $this->get('event_dispatcher')->dispatch(Events::RETRIEVE, new ObjectEvent($resource, $object));

        return $this->getSuccessResponse($resource, $object);
    }

    /**
     * Replaces an element of the collection.
     *
     * @param Request $request
     * @param string  $id
     *
     * @return JsonLdResponse
     *
     * @throws DeserializationException
     */
    public function putAction(Request $request, $id)
    {
        $resource = $this->getResource($request);
        $object = $this->findOrThrowNotFound($resource, $id);

        $context = $resource->getDenormalizationContext();
        $context['object_to_populate'] = $object;

        try {
            $object = $this->get('serializer')->deserialize(
                $request->getContent(),
                $resource->getEntityClass(),
                'json-ld',
                $context
            );
        } catch (Exception $e) {
            throw new DeserializationException($e->getMessage(), $e->getCode(), $e);
        }

        $this->get('event_dispatcher')->dispatch(Events::PRE_UPDATE_VALIDATION, new ObjectEvent($resource, $object));

        $violations = $this->get('validator')->validate($object, null, $resource->getValidationGroups());
        if (0 === count($violations)) {
            // Validation succeed
            $this->get('event_dispatcher')->dispatch(Events::PRE_UPDATE, new ObjectEvent($resource, $object));

            return $this->getSuccessResponse($resource, $object, 202);
        }

        return $this->getErrorResponse($violations);
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

        $this->get('event_dispatcher')->dispatch(Events::PRE_DELETE, new ObjectEvent($resource, $object));

        return new JsonLdResponse(null, 204);
    }
}
