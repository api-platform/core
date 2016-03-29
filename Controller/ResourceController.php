<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Controller;

use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Event\DataEvent;
use Dunglas\ApiBundle\Event\Events;
use Dunglas\ApiBundle\Exception\DeserializationException;
use Dunglas\ApiBundle\Exception\ExceptionInterface;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\JsonLd\Response;
use Dunglas\ApiBundle\Model\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * CRUD operations for the API.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceController extends Controller
{
    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * Gets the Resource associated with the current Request.
     * Must be called before manipulating the resource.
     *
     * @param Request $request
     *
     * @throws InvalidArgumentException
     *
     * @return ResourceInterface
     */
    protected function getResource(Request $request)
    {
        if ($this->resource) {
            return $this->resource;
        }

        if (!$request->attributes->has('_resource')) {
            throw new InvalidArgumentException('The current request doesn\'t have an associated resource.');
        }

        $shortName = $request->attributes->get('_resource');
        if (!($this->resource = $this->get('api.resource_collection')->getResourceForShortName($shortName))) {
            throw new InvalidArgumentException(sprintf('The resource "%s" cannot be found.', $shortName));
        }

        return $this->resource;
    }

    /**
     * Normalizes data using the Symfony Serializer.
     *
     * @param ResourceInterface $resource
     * @param array|object      $data
     * @param int               $status
     * @param array             $headers
     * @param array             $additionalContext
     *
     * @return Response
     */
    protected function getSuccessResponse(
        ResourceInterface $resource,
        $data,
        $status = 200,
        array $headers = [],
        array $additionalContext = []
    ) {
        return new Response(
            $this->get('serializer')->normalize(
                $data, 'json-ld', $resource->getNormalizationContext() + $additionalContext
            ),
            $status,
            $headers
        );
    }

    /**
     * @param ConstraintViolationListInterface $violations
     *
     * @return Response
     */
    protected function getErrorResponse(ConstraintViolationListInterface $violations)
    {
        return new Response($this->get('serializer')->normalize($violations, 'hydra-error'), 400);
    }

    /**
     * Finds an object of throws a 404 error.
     *
     * @param ResourceInterface $resource
     * @param string|int        $id
     *
     * @throws NotFoundHttpException
     *
     * @return object
     */
    protected function findOrThrowNotFound(ResourceInterface $resource, $id)
    {
        $item = $this->get('api.data_provider')->getItem($resource, $id, true);
        if (!$item) {
            throw $this->createNotFoundException();
        }

        return $item;
    }

    /**
     * Gets collection data.
     *
     * @param ResourceInterface $resource
     * @param Request           $request
     *
     * @return PaginatorInterface|array|\Traversable
     */
    protected function getCollectionData(ResourceInterface $resource, Request $request)
    {
        return $this->get('api.data_provider')->getCollection($resource, $request);
    }

    /**
     * Gets the collection.
     *
     * @param Request $request
     *
     * @throws \InvalidArgumentException
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $resource = $this->getResource($request);
        $data = $this->getCollectionData($resource, $request);

        if (
            $request->get($this->container->getParameter('api.collection.pagination.page_parameter_name')) &&
            0 === count($data)
        ) {
            throw $this->createNotFoundException();
        }

        $this->get('event_dispatcher')->dispatch(Events::RETRIEVE_LIST, new DataEvent($resource, $data));

        return $this->getSuccessResponse($resource, $data, 200, [], ['request_uri' => $request->getRequestUri()]);
    }

    /**
     * Adds an element to the collection.
     *
     * @param Request $request
     *
     * @throws DeserializationException
     *
     * @return Response
     */
    public function cpostAction(Request $request)
    {
        $resource = $this->getResource($request);
        try {
            $object = $this->get('serializer')->deserialize(
                $request->getContent(),
                $resource->getEntityClass(),
                'json-ld',
                $resource->getDenormalizationContext()
            );
        } catch (ExceptionInterface $e) {
            throw new DeserializationException($e->getMessage(), $e->getCode(), $e);
        } catch (SerializerExceptionInterface $e) {
            throw new DeserializationException($e->getMessage(), $e->getCode(), $e);
        }

        $this->get('event_dispatcher')->dispatch(Events::PRE_CREATE_VALIDATION, new DataEvent($resource, $object));

        $violations = $this->get('validator')->validate($object, null, $resource->getValidationGroups());
        if (0 === count($violations)) {
            // Validation succeed
            $this->get('event_dispatcher')->dispatch(Events::PRE_CREATE, new DataEvent($resource, $object));

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
     * @throws NotFoundHttpException
     * @throws \InvalidArgumentException
     *
     * @return Response
     */
    public function getAction(Request $request, $id)
    {
        $resource = $this->getResource($request);
        $object = $this->findOrThrowNotFound($resource, $id);

        $this->get('event_dispatcher')->dispatch(Events::RETRIEVE, new DataEvent($resource, $object));

        return $this->getSuccessResponse($resource, $object);
    }

    /**
     * Replaces an element of the collection.
     *
     * @param Request $request
     * @param string  $id
     *
     * @throws DeserializationException
     *
     * @return Response
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
        } catch (ExceptionInterface $e) {
            throw new DeserializationException($e->getMessage(), $e->getCode(), $e);
        } catch (SerializerExceptionInterface $e) {
            throw new DeserializationException($e->getMessage(), $e->getCode(), $e);
        }

        $this->get('event_dispatcher')->dispatch(Events::PRE_UPDATE_VALIDATION, new DataEvent($resource, $object));

        $violations = $this->get('validator')->validate($object, null, $resource->getValidationGroups());
        if (0 === count($violations)) {
            // Validation succeed
            $this->get('event_dispatcher')->dispatch(Events::PRE_UPDATE, new DataEvent($resource, $object));

            return $this->getSuccessResponse($resource, $object);
        }

        return $this->getErrorResponse($violations);
    }

    /**
     * Deletes an element of the collection.
     *
     * @param Request $request
     * @param string  $id
     *
     * @throws NotFoundHttpException
     * @throws \InvalidArgumentException
     *
     * @return Response
     */
    public function deleteAction(Request $request, $id)
    {
        $resource = $this->getResource($request);
        $object = $this->findOrThrowNotFound($resource, $id);

        $this->get('event_dispatcher')->dispatch(Events::PRE_DELETE, new DataEvent($resource, $object));

        return new Response(null, 204);
    }
}
