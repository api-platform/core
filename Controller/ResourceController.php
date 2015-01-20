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
class ResourceController extends AbstractResourceController
{
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

        $violations = $this->get('validator')->validate($object, null, $this->resource->getValidationGroups());
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
