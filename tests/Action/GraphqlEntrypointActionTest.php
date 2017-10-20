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

namespace ApiPlatform\Core\Tests\Action;

use ApiPlatform\Core\Action\GraphqlEntrypointAction;
use ApiPlatform\Core\Bridge\Graphql\ExecutorInterface;
use ApiPlatform\Core\Bridge\Graphql\Type\SchemaBuilderInterface;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class GraphqlEntrypointActionTest extends TestCase
{
    public function testGetAction()
    {
        $request = new Request(['query' => 'graphqlQuery', 'variables' => '["graphqlVariable"]', 'operation' => 'graphqlOperationName']);
        $request->setRequestFormat('json');
        $mockedEntrypoint = $this->mockGraphqlEntrypointAction($request);

        $this->assertEquals(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    public function testPostRawAction()
    {
        $request = new Request(['variables' => '["graphqlVariable"]', 'operation' => 'graphqlOperationName'], [], [], [], [], [], 'graphqlQuery');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/graphql');
        $mockedEntrypoint = $this->mockGraphqlEntrypointAction($request);

        $this->assertEquals(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    public function testPostJsonAction()
    {
        $request = new Request([], [], [], [], [], [], '{"query": "graphqlQuery", "variables": "[\"graphqlVariable\"]", "operation": "graphqlOperationName"}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $mockedEntrypoint = $this->mockGraphqlEntrypointAction($request);

        $this->assertEquals(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    public function testBadContentTypePostAction()
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/xml');
        $mockedEntrypoint = $this->mockGraphqlEntrypointAction($request);

        $this->assertEquals(400, $mockedEntrypoint($request)->getStatusCode());
        $this->assertEquals('{"errors":[{"message":"GraphQL query is not valid","category":"graphql"}]}', $mockedEntrypoint($request)->getContent());
    }

    public function testBadMethodAction()
    {
        $request = new Request();
        $request->setMethod('PUT');
        $mockedEntrypoint = $this->mockGraphqlEntrypointAction($request);

        $this->assertEquals(400, $mockedEntrypoint($request)->getStatusCode());
        $this->assertEquals('{"errors":[{"message":"GraphQL query is not valid","category":"graphql"}]}', $mockedEntrypoint($request)->getContent());
    }

    public function testBadVariablesAction()
    {
        $request = new Request(['query' => 'graphqlQuery', 'variables' => 'graphqlVariable', 'operation' => 'graphqlOperationName']);
        $request->setRequestFormat('json');
        $mockedEntrypoint = $this->mockGraphqlEntrypointAction($request);

        $this->assertEquals(400, $mockedEntrypoint($request)->getStatusCode());
        $this->assertEquals('{"errors":[{"message":"GraphQL variables are not valid JSON","category":"graphql"}]}', $mockedEntrypoint($request)->getContent());
    }

    private function mockGraphqlEntrypointAction(Request $request): GraphqlEntrypointAction
    {
        $schema = $this->prophesize(Schema::class);
        $schemaBuilderProphecy = $this->prophesize(SchemaBuilderInterface::class);
        $schemaBuilderProphecy->getSchema()->willReturn($schema->reveal());

        $executionResultProphecy = $this->prophesize(ExecutionResult::class);
        $executionResultProphecy->toArray(true)->willReturn(['GraphQL']);
        $executorProphecy = $this->prophesize(ExecutorInterface::class);
        $executorProphecy->executeQuery(Argument::is($schema->reveal()), 'graphqlQuery', null, null, ['graphqlVariable'], 'graphqlOperationName')->willReturn($executionResultProphecy->reveal());

        $twigProphecy = $this->prophesize(\Twig_Environment::class);

        return new GraphqlEntrypointAction($schemaBuilderProphecy->reveal(), $executorProphecy->reveal(), $twigProphecy->reveal(), true, true, '');
    }
}
