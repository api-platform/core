<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\tests\Action;

use ApiPlatform\Core\Action\ExceptionAction;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ExceptionActionTest extends \PHPUnit_Framework_TestCase
{
    public function testActionWithCatchableException()
    {
        $serializerException = $this->prophesize(ExceptionInterface::class);
        $serializerException->willExtend(\Exception::class);

        $flattenException = FlattenException::create($serializerException->reveal());

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize($flattenException, 'jsonproblem')->willReturn();

        $exceptionAction = new ExceptionAction($serializer->reveal(), ['jsonproblem' => ['application/problem+json'], 'jsonld' => ['application/ld+json']], [ExceptionInterface::class => Response::HTTP_BAD_REQUEST, InvalidArgumentException::class => Response::HTTP_BAD_REQUEST]);

        $request = new Request();
        $request->setFormat('jsonproblem', 'application/problem+json');

        $expected = new Response('', Response::HTTP_BAD_REQUEST, [
            'Content-Type' => 'application/problem+json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ]);

        $this->assertEquals($expected, $exceptionAction($flattenException, $request));
    }

    public function testActionWithUncatchableException()
    {
        $serializerException = $this->prophesize(ExceptionInterface::class);
        $serializerException->willExtend(\Exception::class);

        $flattenException = FlattenException::create($serializerException->reveal());

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize($flattenException, 'jsonproblem')->willReturn();

        $exceptionAction = new ExceptionAction($serializer->reveal(), ['jsonproblem' => ['application/problem+json'], 'jsonld' => ['application/ld+json']]);

        $request = new Request();
        $request->setFormat('jsonproblem', 'application/problem+json');

        $expected = new Response('', Response::HTTP_INTERNAL_SERVER_ERROR, [
            'Content-Type' => 'application/problem+json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ]);

        $this->assertEquals($expected, $exceptionAction($flattenException, $request));
    }
}
