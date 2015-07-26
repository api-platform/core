<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Action;

use Dunglas\ApiBundle\Event\DataEvent;
use Dunglas\ApiBundle\Event\Events;
use Dunglas\ApiBundle\Exception\RuntimeException;
use Dunglas\ApiBundle\Exception\ValidationException;
use Dunglas\ApiBundle\Model\DataProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Updates a resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PutItemAction
{
    use ActionUtilTrait;

    /**
     * @var DataProviderInterface
     */
    private $dataProvider;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(DataProviderInterface $dataProvider, SerializerInterface $serializer, ValidatorInterface $validator, EventDispatcherInterface $eventDispatcher)
    {
        $this->dataProvider = $dataProvider;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Create a new item.
     *
     * @param Request    $request
     * @param string|int $id
     *
     * @return mixed
     *
     * @throws NotFoundHttpException
     * @throws RuntimeException
     */
    public function __invoke(Request $request, $id)
    {
        list($resourceType, $format) = $this->extractAttributes($request);
        $data = $this->getItem($this->dataProvider, $resourceType, $id);

        $context = $resourceType->getDenormalizationContext();
        $context['object_to_populate'] = $data;

        $data = $this->serializer->deserialize(
            $request->getContent(),
            $resourceType->getEntityClass(),
            $format,
            $context
        );

        $this->eventDispatcher->dispatch(Events::PRE_UPDATE_VALIDATION, new DataEvent($resourceType, $data));

        $violations = $this->validator->validate($data, null, $resourceType->getValidationGroups());
        if (0 !== count($violations)) {
            throw new ValidationException($violations);
        }

        // Validation succeed
        $this->eventDispatcher->dispatch(Events::PRE_CREATE, new DataEvent($resourceType, $data));

        return $data;
    }
}
