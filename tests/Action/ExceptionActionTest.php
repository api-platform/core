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
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ExceptionActionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetException()
    {
        $flattenException = $this->prophesize(FlattenException::class);
        $flattenException->getClass()->willReturn(InvalidArgumentException::class);
        $flattenException->setStatusCode(Response::HTTP_BAD_REQUEST)->willReturn();
        $flattenException->getHeaders()->willReturn(['Content-Type' => 'application/problem+json']);

        $flattenException->getStatusCode()->willReturn(Response::HTTP_BAD_REQUEST);
        $serializer = $this->prophesize(SerializerInterface::class);
        $exceptionAction = new ExceptionAction($serializer->reveal(), ['jsonproblem' => ['application/problem+json'], 'jsonld' => ['application/ld+json']]);
        $request = new Request();
        $request->setFormat('jsonproblem', 'application/problem+json');
        $serializer->serialize($flattenException, 'jsonproblem')->willReturn();
        $expected = new Response('', Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/problem+json']);

        $this->assertEquals($expected, $exceptionAction($flattenException->reveal(), $request));
    }
}
