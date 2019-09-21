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
        $request->setFormat('graphql', 'application/graphql');
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

    /**
     * @dataProvider multipartRequestProvider
     */
    public function testMultipartRequestAction(?string $operations, ?string $map, array $files, array $variables, Response $expectedResponse)
    {
        $requestParams = [];
        if ($operations) {
            $requestParams['operations'] = $operations;
        }
        if ($map) {
            $requestParams['map'] = $map;
        }
        $request = new Request([], $requestParams, [], [], $files);
        $request->setFormat('multipart', 'multipart/form-data');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'multipart/form-data');

        $mockedEntrypoint = $this->getEntrypointAction($variables);

        $this->assertEquals($expectedResponse->getContent(), $mockedEntrypoint($request)->getContent());
    }

    public function multipartRequestProvider(): array
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            null,
            UPLOAD_ERR_OK
        );

        return [
            'upload a single file' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operation": "graphqlOperationName"}',
                '{"file": ["variables.file"]}',
                ['file' => $file],
                ['file' => $file],
                new JsonResponse(['GraphQL']),
            ],
            'upload multiple files' => [
                '{"query": "graphqlQuery", "variables": {"files": [null, null, null]}, "operation": "graphqlOperationName"}',
                '{"0": ["variables.files.0"], "1": ["variables.files.1"], "2": ["variables.files.2"]}',
                [
                    '0' => $file,
                    '1' => $file,
                    '2' => $file,
                ],
                [
                    'files' => [
                        $file,
                        $file,
                        $file,
                    ],
                ],
                new JsonResponse(['GraphQL']),
            ],
            'upload without providing operations' => [
                null,
                '{"file": ["variables.file"]}',
                ['file' => $file],
                ['file' => $file],
                new Response('{"errors":[{"message":"GraphQL multipart request does not respect the specification.","extensions":{"category":"user"}}]}'),
            ],
            'upload without providing map' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operation": "graphqlOperationName"}',
                null,
                ['file' => $file],
                ['file' => null],
                new Response('{"errors":[{"message":"GraphQL multipart request does not respect the specification.","extensions":{"category":"user"}}]}'),
            ],
            'upload with invalid json' => [
                '{invalid}',
                '{"file": ["file"]}',
                ['file' => $file],
                ['file' => null],
                new Response('{"errors":[{"message":"GraphQL data is not valid JSON.","extensions":{"category":"user"}}]}'),
            ],
            'upload with invalid map JSON' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operation": "graphqlOperationName"}',
                '{invalid}',
                ['file' => $file],
                ['file' => null],
                new Response('{"errors":[{"message":"GraphQL multipart request map is not valid JSON.","extensions":{"category":"user"}}]}'),
            ],
            'upload with no file' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operation": "graphqlOperationName"}',
                '{"file": ["file"]}',
                [],
                ['file' => null],
                new Response('{"errors":[{"message":"GraphQL multipart request file has not been sent correctly.","extensions":{"category":"user"}}]}'),
            ],
            'upload with wrong map' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operation": "graphqlOperationName"}',
                '{"file": ["file"]}',
                ['file' => $file],
                ['file' => null],
                new Response('{"errors":[{"message":"GraphQL multipart request path in map is invalid.","extensions":{"category":"user"}}]}'),
            ],
            'upload when variable path does not exist' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operation": "graphqlOperationName"}',
                '{"file": ["variables.wrong"]}',
                ['file' => $file],
                ['file' => null],
                new Response('{"errors":[{"message":"GraphQL multipart request path in map does not match the variables.","extensions":{"category":"user"}}]}'),
            ],
        ];
    }

    public function testBadContentTypePostAction()
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/xml');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEquals(400, $mockedEntrypoint($request)->getStatusCode());
        $this->assertEquals('{"errors":[{"message":"GraphQL query is not valid.","extensions":{"category":"user"}}]}', $mockedEntrypoint($request)->getContent());
    }

    public function testBadMethodAction()
    {
        $request = new Request();
        $request->setMethod('PUT');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEquals(400, $mockedEntrypoint($request)->getStatusCode());
        $this->assertEquals('{"errors":[{"message":"GraphQL query is not valid.","extensions":{"category":"user"}}]}', $mockedEntrypoint($request)->getContent());
    }

    public function testBadVariablesAction()
    {
        $request = new Request(['query' => 'graphqlQuery', 'variables' => 'graphqlVariable', 'operation' => 'graphqlOperationName']);
        $request->setRequestFormat('json');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEquals(400, $mockedEntrypoint($request)->getStatusCode());
        $this->assertEquals('{"errors":[{"message":"GraphQL variables are not valid JSON.","extensions":{"category":"user"}}]}', $mockedEntrypoint($request)->getContent());
    }

    private function getEntrypointAction(array $variables = ['graphqlVariable']): EntrypointAction
    {
        $schema = $this->prophesize(Schema::class);
        $schemaBuilderProphecy = $this->prophesize(SchemaBuilderInterface::class);
        $schemaBuilderProphecy->getSchema()->willReturn($schema->reveal());

        $executionResultProphecy = $this->prophesize(ExecutionResult::class);
        $executionResultProphecy->toArray(false)->willReturn(['GraphQL']);
        $executorProphecy = $this->prophesize(ExecutorInterface::class);
        $executorProphecy->executeQuery(Argument::is($schema->reveal()), 'graphqlQuery', null, null, $variables, 'graphqlOperationName')->willReturn($executionResultProphecy->reveal());

        $twigProphecy = $this->prophesize(TwigEnvironment::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);

        $graphiQlAction = new GraphiQlAction($twigProphecy->reveal(), $routerProphecy->reveal(), true);
        $graphQlPlaygroundAction = new GraphQlPlaygroundAction($twigProphecy->reveal(), $routerProphecy->reveal(), true);

        return new EntrypointAction($schemaBuilderProphecy->reveal(), $executorProphecy->reveal(), $graphiQlAction, $graphQlPlaygroundAction, false, true, true, 'graphiql');
    }
}
