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

namespace ApiPlatform\Core\Tests\GraphQl\Action;

use ApiPlatform\Core\GraphQl\Action\EntrypointAction;
use ApiPlatform\Core\GraphQl\Action\GraphiQlAction;
use ApiPlatform\Core\GraphQl\Action\GraphQlPlaygroundAction;
use ApiPlatform\Core\GraphQl\ExecutorInterface;
use ApiPlatform\Core\GraphQl\Type\SchemaBuilderInterface;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class EntrypointActionTest extends TestCase
{
    /**
     * Hack to avoid transient failing test because of Date header.
     */
    private function assertEqualsWithoutDateHeader(JsonResponse $expected, Response $actual)
    {
        $expected->headers->remove('Date');
        $actual->headers->remove('Date');
        $this->assertEquals($expected, $actual);
    }

    public function testGetHtmlAction(): void
    {
        $request = new Request();
        $request->setRequestFormat('html');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertInstanceOf(Response::class, $mockedEntrypoint($request));
    }

    public function testGetAction()
    {
        $request = new Request(['query' => 'graphqlQuery', 'variables' => '["graphqlVariable"]', 'operation' => 'graphqlOperationName']);
        $request->setRequestFormat('json');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEqualsWithoutDateHeader(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    public function testPostRawAction()
    {
        $request = new Request(['variables' => '["graphqlVariable"]', 'operation' => 'graphqlOperationName'], [], [], [], [], [], 'graphqlQuery');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/graphql');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEqualsWithoutDateHeader(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    public function testPostJsonAction()
    {
        $request = new Request([], [], [], [], [], [], '{"query": "graphqlQuery", "variables": "[\"graphqlVariable\"]", "operation": "graphqlOperationName"}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEqualsWithoutDateHeader(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    public function testFormMultipartAction()
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            null,
            UPLOAD_ERR_OK
        );
        $request = new Request([], ['operations' => '{"query": "graphqlQuery", "variables": {"file":null}, "operation": "graphqlOperationName"}', 'map' => '{"0":["variables.file"]}'], [], [], ['0' => $file]);
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'multipart/form-data');

        $schema = $this->prophesize(Schema::class);
        $schemaBuilderProphecy = $this->prophesize(SchemaBuilderInterface::class);
        $schemaBuilderProphecy->getSchema()->willReturn($schema->reveal());

        $executionResultProphecy = $this->prophesize(ExecutionResult::class);
        $executionResultProphecy->toArray(3)->willReturn(['GraphQL']);
        $executorProphecy = $this->prophesize(ExecutorInterface::class);
        $executorProphecy->executeQuery(Argument::is($schema->reveal()), 'graphqlQuery', null, null, ['file' => $file], 'graphqlOperationName')->willReturn($executionResultProphecy->reveal());

        $twigProphecy = $this->prophesize(TwigEnvironment::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);

        $graphiQlAction = new GraphiQlAction($twigProphecy->reveal(), $routerProphecy->reveal(), true);
        $graphQlPlaygroundAction = new GraphQlPlaygroundAction($twigProphecy->reveal(), $routerProphecy->reveal(), true);

        $mockedEntrypoint = new EntrypointAction($schemaBuilderProphecy->reveal(), $executorProphecy->reveal(), $graphiQlAction, $graphQlPlaygroundAction, true, true, true, 'graphiql');

        $this->assertEqualsWithoutDateHeader(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    public function testBadContentTypePostAction()
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/xml');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEquals(400, $mockedEntrypoint($request)->getStatusCode());
        $this->assertEquals('{"errors":[{"message":"GraphQL query is not valid","extensions":{"category":"graphql"}}]}', $mockedEntrypoint($request)->getContent());
    }

    public function testBadMethodAction()
    {
        $request = new Request();
        $request->setMethod('PUT');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEquals(400, $mockedEntrypoint($request)->getStatusCode());
        $this->assertEquals('{"errors":[{"message":"GraphQL query is not valid","extensions":{"category":"graphql"}}]}', $mockedEntrypoint($request)->getContent());
    }

    public function testBadVariablesAction()
    {
        $request = new Request(['query' => 'graphqlQuery', 'variables' => 'graphqlVariable', 'operation' => 'graphqlOperationName']);
        $request->setRequestFormat('json');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEquals(400, $mockedEntrypoint($request)->getStatusCode());
        $this->assertEquals('{"errors":[{"message":"GraphQL variables are not valid JSON","extensions":{"category":"graphql"}}]}', $mockedEntrypoint($request)->getContent());
    }

    private function getEntrypointAction(): EntrypointAction
    {
        $schema = $this->prophesize(Schema::class);
        $schemaBuilderProphecy = $this->prophesize(SchemaBuilderInterface::class);
        $schemaBuilderProphecy->getSchema()->willReturn($schema->reveal());

        $executionResultProphecy = $this->prophesize(ExecutionResult::class);
        $executionResultProphecy->toArray(3)->willReturn(['GraphQL']);
        $executorProphecy = $this->prophesize(ExecutorInterface::class);
        $executorProphecy->executeQuery(Argument::is($schema->reveal()), 'graphqlQuery', null, null, ['graphqlVariable'], 'graphqlOperationName')->willReturn($executionResultProphecy->reveal());

        $twigProphecy = $this->prophesize(TwigEnvironment::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);

        $graphiQlAction = new GraphiQlAction($twigProphecy->reveal(), $routerProphecy->reveal(), true);
        $graphQlPlaygroundAction = new GraphQlPlaygroundAction($twigProphecy->reveal(), $routerProphecy->reveal(), true);

        return new EntrypointAction($schemaBuilderProphecy->reveal(), $executorProphecy->reveal(), $graphiQlAction, $graphQlPlaygroundAction, true, true, true, 'graphiql');
    }
}
