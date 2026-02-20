<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\Controller;

use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InputDtoWithNameConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Reproduces the controller use case from issue #7705:
 * the name converter must be applied when deserializing input DTOs via SerializerInterface.
 */
class InputDtoWithNameConverterController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $input = $this->serializer->deserialize($request->getContent(), InputDtoWithNameConverter::class, 'json');

        return new JsonResponse($input);
    }
}
