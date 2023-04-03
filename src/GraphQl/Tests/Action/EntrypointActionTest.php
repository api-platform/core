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

namespace ApiPlatform\GraphQl\Tests\Action;

use ApiPlatform\GraphQl\Action\EntrypointAction;
use ApiPlatform\GraphQl\Action\GraphiQlAction;
use ApiPlatform\GraphQl\Action\GraphQlPlaygroundAction;
use ApiPlatform\GraphQl\Error\ErrorHandler;
use ApiPlatform\GraphQl\ExecutorInterface;
use ApiPlatform\GraphQl\Serializer\Exception\ErrorNormalizer;
use ApiPlatform\GraphQl\Serializer\Exception\HttpExceptionNormalizer;
use ApiPlatform\GraphQl\Type\SchemaBuilderInterface;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Serializer;
use Twig\Environment as TwigEnvironment;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class EntrypointActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * Hack to avoid transient failing test because of Date header.
     */
    private function assertEqualsWithoutDateHeader(JsonResponse $expected, Response $actual): void
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

    public function testGetAction(): void
    {
        $request = new Request(['query' => 'graphqlQuery', 'variables' => '["graphqlVariable"]', 'operationName' => 'graphqlOperationName']);
        $request->setRequestFormat('json');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEqualsWithoutDateHeader(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    public function testPostRawAction(): void
    {
        $request = new Request(['variables' => '["graphqlVariable"]', 'operationName' => 'graphqlOperationName'], [], [], [], [], [], 'graphqlQuery');
        $request->setFormat('graphql', 'application/graphql');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/graphql');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEqualsWithoutDateHeader(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    public function testPostJsonAction(): void
    {
        $request = new Request([], [], [], [], [], [], '{"query": "graphqlQuery", "variables": "[\"graphqlVariable\"]", "operationName": "graphqlOperationName"}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertEqualsWithoutDateHeader(new JsonResponse(['GraphQL']), $mockedEntrypoint($request));
    }

    /**
     * @dataProvider multipartRequestProvider
     */
    public function testMultipartRequestAction(?string $operations, ?string $map, array $files, array $variables, Response $expectedResponse): void
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

        $this->assertSame($expectedResponse->getContent(), $mockedEntrypoint($request)->getContent());
    }

    public static function multipartRequestProvider(): array
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            null,
            \UPLOAD_ERR_OK
        );

        return [
            'upload a single file' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operationName": "graphqlOperationName"}',
                '{"file": ["variables.file"]}',
                ['file' => $file],
                ['file' => $file],
                new JsonResponse(['GraphQL']),
            ],
            'upload multiple files' => [
                '{"query": "graphqlQuery", "variables": {"files": [null, null, null]}, "operationName": "graphqlOperationName"}',
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
                new Response(\defined(Error::class.'::CATEGORY_GRAPHQL') ? '{"errors":[{"message":"GraphQL multipart request does not respect the specification.","extensions":{"category":"user","status":400}}]}' : '{"errors":[{"message":"GraphQL multipart request does not respect the specification.","extensions":{"status":400}}]}'),
            ],
            'upload without providing map' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operationName": "graphqlOperationName"}',
                null,
                ['file' => $file],
                ['file' => null],
                new Response(\defined(Error::class.'::CATEGORY_GRAPHQL') ? '{"errors":[{"message":"GraphQL multipart request does not respect the specification.","extensions":{"category":"user","status":400}}]}' : '{"errors":[{"message":"GraphQL multipart request does not respect the specification.","extensions":{"status":400}}]}'),
            ],
            'upload with invalid json' => [
                '{invalid}',
                '{"file": ["file"]}',
                ['file' => $file],
                ['file' => null],
                new Response(\defined(Error::class.'::CATEGORY_GRAPHQL') ? '{"errors":[{"message":"GraphQL data is not valid JSON.","extensions":{"category":"user","status":400}}]}' : '{"errors":[{"message":"GraphQL data is not valid JSON.","extensions":{"status":400}}]}'),
            ],
            'upload with invalid map JSON' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operationName": "graphqlOperationName"}',
                '{invalid}',
                ['file' => $file],
                ['file' => null],
                new Response(\defined(Error::class.'::CATEGORY_GRAPHQL') ? '{"errors":[{"message":"GraphQL multipart request map is not valid JSON.","extensions":{"category":"user","status":400}}]}' : '{"errors":[{"message":"GraphQL multipart request map is not valid JSON.","extensions":{"status":400}}]}'),
            ],
            'upload with no file' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operationName": "graphqlOperationName"}',
                '{"file": ["file"]}',
                [],
                ['file' => null],
                new Response(\defined(Error::class.'::CATEGORY_GRAPHQL') ? '{"errors":[{"message":"GraphQL multipart request file has not been sent correctly.","extensions":{"category":"user","status":400}}]}' : '{"errors":[{"message":"GraphQL multipart request file has not been sent correctly.","extensions":{"status":400}}]}'),
            ],
            'upload with wrong map' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operationName": "graphqlOperationName"}',
                '{"file": ["file"]}',
                ['file' => $file],
                ['file' => null],
                new Response(\defined(Error::class.'::CATEGORY_GRAPHQL') ? '{"errors":[{"message":"GraphQL multipart request path in map is invalid.","extensions":{"category":"user","status":400}}]}' : '{"errors":[{"message":"GraphQL multipart request path in map is invalid.","extensions":{"status":400}}]}'),
            ],
            'upload when variable path does not exist' => [
                '{"query": "graphqlQuery", "variables": {"file": null}, "operationName": "graphqlOperationName"}',
                '{"file": ["variables.wrong"]}',
                ['file' => $file],
                ['file' => null],
                new Response(\defined(Error::class.'::CATEGORY_GRAPHQL') ? '{"errors":[{"message":"GraphQL multipart request path in map does not match the variables.","extensions":{"category":"user","status":400}}]}' : '{"errors":[{"message":"GraphQL multipart request path in map does not match the variables.","extensions":{"status":400}}]}'),
            ],
        ];
    }

    public function testBadContentTypePostAction(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/xml');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertSame(200, $mockedEntrypoint($request)->getStatusCode());
        // graphql-php < 15
        if (\defined(Error::class.'::CATEGORY_GRAPHQL')) {
            $this->assertSame('{"errors":[{"message":"GraphQL query is not valid.","extensions":{"category":"user","status":400}}]}', $mockedEntrypoint($request)->getContent());
        } else {
            $this->assertSame('{"errors":[{"message":"GraphQL query is not valid.","extensions":{"status":400}}]}', $mockedEntrypoint($request)->getContent());
        }
    }

    public function testBadMethodAction(): void
    {
        $request = new Request();
        $request->setMethod('PUT');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertSame(200, $mockedEntrypoint($request)->getStatusCode());
        // graphql-php < 15
        if (\defined(Error::class.'::CATEGORY_GRAPHQL')) {
            $this->assertSame('{"errors":[{"message":"GraphQL query is not valid.","extensions":{"category":"user","status":400}}]}', $mockedEntrypoint($request)->getContent());
        } else {
            $this->assertSame('{"errors":[{"message":"GraphQL query is not valid.","extensions":{"status":400}}]}', $mockedEntrypoint($request)->getContent());
        }
    }

    public function testBadVariablesAction(): void
    {
        $request = new Request(['query' => 'graphqlQuery', 'variables' => 'graphqlVariable', 'operationName' => 'graphqlOperationName']);
        $request->setRequestFormat('json');
        $mockedEntrypoint = $this->getEntrypointAction();

        $this->assertSame(200, $mockedEntrypoint($request)->getStatusCode());
        // graphql-php < 15
        if (\defined(Error::class.'::CATEGORY_GRAPHQL')) {
            $this->assertSame('{"errors":[{"message":"GraphQL variables are not valid JSON.","extensions":{"category":"user","status":400}}]}', $mockedEntrypoint($request)->getContent());
        } else {
            $this->assertSame('{"errors":[{"message":"GraphQL variables are not valid JSON.","extensions":{"status":400}}]}', $mockedEntrypoint($request)->getContent());
        }
    }

    private function getEntrypointAction(array $variables = ['graphqlVariable']): EntrypointAction
    {
        $schema = $this->prophesize(Schema::class);
        $schemaBuilderProphecy = $this->prophesize(SchemaBuilderInterface::class);
        $schemaBuilderProphecy->getSchema()->willReturn($schema->reveal());

        $normalizer = new Serializer([
            new HttpExceptionNormalizer(),
            new ErrorNormalizer(),
        ]);
        $errorHandler = new ErrorHandler();

        $executionResultProphecy = $this->prophesize(ExecutionResult::class);
        $executionResultProphecy->toArray(DebugFlag::NONE)->willReturn(['GraphQL']);
        $executionResultProphecy->setErrorFormatter(Argument::type('callable'))->willReturn($executionResultProphecy);
        $executionResultProphecy->setErrorsHandler($errorHandler)->willReturn($executionResultProphecy);
        $executorProphecy = $this->prophesize(ExecutorInterface::class);
        $executorProphecy->executeQuery(Argument::is($schema->reveal()), 'graphqlQuery', null, null, $variables, 'graphqlOperationName')->willReturn($executionResultProphecy->reveal());

        $twigProphecy = $this->prophesize(TwigEnvironment::class);
        $twigProphecy->render(Argument::cetera())->willReturn('');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_graphql_entrypoint')->willReturn('/graphiql');

        $graphiQlAction = new GraphiQlAction($twigProphecy->reveal(), $routerProphecy->reveal(), true);
        $graphQlPlaygroundAction = new GraphQlPlaygroundAction($twigProphecy->reveal(), $routerProphecy->reveal(), true);

        return new EntrypointAction($schemaBuilderProphecy->reveal(), $executorProphecy->reveal(), $graphiQlAction, $graphQlPlaygroundAction, $normalizer, $errorHandler, false, true, true, 'graphiql');
    }
}
