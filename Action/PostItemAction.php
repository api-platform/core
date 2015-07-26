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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Add a new resource to a collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PostItemAction
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
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
    }

    /**
     * Create a new item.
     *
     * @param Request $request
     *
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function __invoke(Request $request)
    {
        list($resourceType, $format) = $this->extractAttributes($request);

        $data = $this->serializer->deserialize(
            $request->getContent(),
            $resourceType->getEntityClass(),
            $format,
            $resourceType->getDenormalizationContext()
        );

        $this->eventDispatcher->dispatch(Events::PRE_CREATE_VALIDATION, new DataEvent($resourceType, $data));

        $violations = $this->validator->validate($data, null, $resourceType->getValidationGroups());
        if (0 !== count($violations)) {
            throw new ValidationException($violations);
        }

        // Validation succeed
        $this->eventDispatcher->dispatch(Events::PRE_CREATE, new DataEvent($resourceType, $data));

        return $data;
    }
}
