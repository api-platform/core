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

namespace ApiPlatform\Mcp\Tests\Server;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Mcp\JsonSchema\ToolInputSchemaFactory;
use ApiPlatform\Mcp\Server\Handler;
use ApiPlatform\Mcp\State\ToolProvider;
use ApiPlatform\Metadata\Exception\BadRequestException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Request\PingRequest;
use Mcp\Schema\Request\ReadResourceRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\RequestStack;

class HandlerTest extends TestCase
{
    public function testSupportsACallToolRequestWithAKnownOperation(): void
    {
        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->expects($this->once())
            ->method('create')
            ->with('createDummy')
            ->willReturn(new McpTool(name: 'createDummy', class: \stdClass::class));

        $handler = $this->createHandler($operationMetadataFactory);

        $this->assertTrue($handler->supports(new CallToolRequest('createDummy', [])));
    }

    public function testDoesNotSupportACallToolRequestWithAnUnknownOperation(): void
    {
        $handler = $this->createHandler($this->createOperationMetadataFactory(null));

        $this->assertFalse($handler->supports(new CallToolRequest('unknown', [])));
    }

    /**
     * A `resources/read` request carries a URI where a tool carries a name.
     */
    public function testSupportsAReadResourceRequestWithAKnownOperation(): void
    {
        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->expects($this->once())
            ->method('create')
            ->with('dummy://docs')
            ->willReturn(new McpResource(uri: 'dummy://docs', name: 'docs', class: \stdClass::class));

        $handler = $this->createHandler($operationMetadataFactory);

        $this->assertTrue($handler->supports(new ReadResourceRequest('dummy://docs')));
    }

    public function testDoesNotSupportAReadResourceRequestWithAnUnknownOperation(): void
    {
        $handler = $this->createHandler($this->createOperationMetadataFactory(null));

        $this->assertFalse($handler->supports(new ReadResourceRequest('dummy://unknown')));
    }

    public function testDoesNotSupportOtherRequests(): void
    {
        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->expects($this->never())->method('create');

        $handler = $this->createHandler($operationMetadataFactory);

        $this->assertFalse($handler->supports(new PingRequest()));
    }

    public function testHandleReturnsAMethodNotFoundErrorForAnUnknownOperation(): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->never())->method('provide');

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->never())->method('process');

        $handler = $this->createHandler($this->createOperationMetadataFactory(null), $provider, $processor);

        $error = $handler->handle((new CallToolRequest('unknown', []))->withId('req-1'), $this->createStub(SessionInterface::class));

        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame(Error::METHOD_NOT_FOUND, $error->code);
        $this->assertSame('MCP operation "unknown" not found.', $error->message);
        $this->assertSame('req-1', $error->id);
    }

    public function testHandleACallToolRequest(): void
    {
        $operation = (new McpTool(name: 'createDummy', class: \stdClass::class, extraProperties: ['foo' => 'bar']))
            ->withUriVariables(['id' => new Link(parameterName: 'id')]);

        $body = new \stdClass();
        $expected = new Response('req-1', new CallToolResult([new TextContent('{"id":1}')]));

        $request = (new CallToolRequest('createDummy', ['id' => '42', 'name' => 'foo']))->withId('req-1');
        $session = $this->createStub(SessionInterface::class);

        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')->willReturnCallback(
            function (Operation $operation, array $uriVariables, array $context) use ($body, $request, $session): object {
                // Only declared URI variables are extracted from the arguments.
                $this->assertSame(['id' => '42'], $uriVariables);

                $this->assertSame($request, $context['mcp_request']);
                $this->assertSame($session, $context['mcp_session']);
                $this->assertSame(['id' => '42'], $context['uri_variables']);
                $this->assertSame(\stdClass::class, $context['resource_class']);
                // The full argument list stays available as the tool payload.
                $this->assertSame(['id' => '42', 'name' => 'foo'], $context['mcp_data']);

                $this->assertTrue($operation->getExtraProperties()['_api_disable_swagger_provider']);
                $this->assertSame('bar', $operation->getExtraProperties()['foo']);

                $this->assertFalse($operation->canNegotiateContent());
                $this->assertFalse($operation->canValidate());
                $this->assertTrue($operation->canRead());
                $this->assertFalse($operation->canDeserialize());
                $this->assertSame(ToolProvider::class, $operation->getProvider());

                return $body;
            }
        );

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturnCallback(
            function (mixed $data, Operation $operation, array $uriVariables, array $context) use ($body, $expected): Response {
                $this->assertSame($body, $data);
                $this->assertSame(['id' => '42'], $uriVariables);
                $this->assertTrue($operation->canWrite());
                $this->assertFalse($operation->canSerialize());

                return $expected;
            }
        );

        $handler = $this->createHandler($this->createOperationMetadataFactory($operation), $provider, $processor);

        $this->assertSame($expected, $handler->handle($request, $session));
    }

    /**
     * Reading a resource takes no arguments, so it must not build URI variables nor
     * a tool payload out of the request.
     */
    public function testHandleAReadResourceRequest(): void
    {
        $operation = (new McpResource(uri: 'dummy://docs', name: 'docs', class: \stdClass::class))
            ->withUriVariables(['id' => new Link(parameterName: 'id')]);

        $expected = new Response('req-1', new CallToolResult([new TextContent('{"id":1}')]));

        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')->willReturnCallback(
            function (Operation $operation, array $uriVariables, array $context): object {
                $this->assertSame([], $uriVariables);
                $this->assertSame([], $context['uri_variables']);
                $this->assertArrayNotHasKey('mcp_data', $context);

                return new \stdClass();
            }
        );

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturn($expected);

        $handler = $this->createHandler($this->createOperationMetadataFactory($operation), $provider, $processor);

        $this->assertSame($expected, $handler->handle((new ReadResourceRequest('dummy://docs'))->withId('req-1'), $this->createStub(SessionInterface::class)));
    }

    /**
     * The handler only fills in the flags the operation left undecided.
     */
    public function testHandleKeepsExplicitOperationFlags(): void
    {
        $operation = (new McpTool(name: 'createDummy', class: \stdClass::class))
            ->withContentNegotiation(true)
            ->withValidate(true)
            ->withRead(false)
            ->withDeserialize(true)
            ->withProvider('my_provider')
            ->withWrite(false)
            ->withSerialize(true);

        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')->willReturnCallback(
            function (Operation $operation): object {
                $this->assertTrue($operation->canNegotiateContent());
                $this->assertTrue($operation->canValidate());
                $this->assertFalse($operation->canRead());
                $this->assertTrue($operation->canDeserialize());
                $this->assertSame('my_provider', $operation->getProvider());

                return new \stdClass();
            }
        );

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturnCallback(
            function (mixed $data, Operation $operation): Response {
                $this->assertFalse($operation->canWrite());
                $this->assertTrue($operation->canSerialize());

                return new Response('req-1', new CallToolResult([new TextContent('{}')]));
            }
        );

        $handler = $this->createHandler($this->createOperationMetadataFactory($operation), $provider, $processor);

        $handler->handle((new CallToolRequest('createDummy', []))->withId('req-1'), $this->createStub(SessionInterface::class));
    }

    /**
     * The state pipeline stores its intermediate results on the HTTP request; the
     * processor reads them back from the context.
     */
    public function testHandleCopiesTheStatePipelineAttributesForTools(): void
    {
        $previousData = new \stdClass();
        $data = new \stdClass();
        $readData = new \stdClass();
        $mappedData = new \stdClass();

        $httpRequest = new HttpRequest();
        $httpRequest->attributes->set('previous_data', $previousData);
        $httpRequest->attributes->set('data', $data);
        $httpRequest->attributes->set('read_data', $readData);
        $httpRequest->attributes->set('mapped_data', $mappedData);

        $operation = new McpTool(name: 'createDummy', class: \stdClass::class);

        $provider = $this->createStub(ProviderInterface::class);
        $provider->method('provide')->willReturn(new \stdClass());

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturnCallback(
            function (mixed $data_, Operation $operation, array $uriVariables, array $context) use ($httpRequest, $previousData, $data, $readData, $mappedData): Response {
                $this->assertSame($httpRequest, $context['request']);
                $this->assertSame($previousData, $context['previous_data']);
                $this->assertSame($data, $context['data']);
                $this->assertSame($readData, $context['read_data']);
                $this->assertSame($mappedData, $context['mapped_data']);

                return new Response('req-1', new CallToolResult([new TextContent('{}')]));
            }
        );

        $requestStack = new RequestStack();
        $requestStack->push($httpRequest);

        $handler = $this->createHandler($this->createOperationMetadataFactory($operation), $provider, $processor, $requestStack);

        $handler->handle((new CallToolRequest('createDummy', []))->withId('req-1'), $this->createStub(SessionInterface::class));
    }

    public function testHandleDoesNotCopyTheStatePipelineAttributesForResources(): void
    {
        $httpRequest = new HttpRequest();
        $httpRequest->attributes->set('previous_data', new \stdClass());

        $operation = new McpResource(uri: 'dummy://docs', name: 'docs', class: \stdClass::class);

        $provider = $this->createStub(ProviderInterface::class);
        $provider->method('provide')->willReturn(new \stdClass());

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturnCallback(
            function (mixed $data, Operation $operation, array $uriVariables, array $context): Response {
                $this->assertArrayNotHasKey('previous_data', $context);
                $this->assertArrayNotHasKey('data', $context);
                $this->assertArrayNotHasKey('read_data', $context);
                $this->assertArrayNotHasKey('mapped_data', $context);

                return new Response('req-1', new CallToolResult([new TextContent('{}')]));
            }
        );

        $requestStack = new RequestStack();
        $requestStack->push($httpRequest);

        $handler = $this->createHandler($this->createOperationMetadataFactory($operation), $provider, $processor, $requestStack);

        $handler->handle((new ReadResourceRequest('dummy://docs'))->withId('req-1'), $this->createStub(SessionInterface::class));
    }

    /**
     * MCP has no HTTP response to carry a status code, so a caller-facing exception
     * becomes a JSON-RPC error carrying its message.
     *
     * @see https://github.com/api-platform/core/pull/8436
     */
    public function testHandleConvertsAProviderHttpExceptionIntoAJsonRpcError(): void
    {
        $operation = new McpTool(name: 'createDummy', class: \stdClass::class);

        $provider = $this->createStub(ProviderInterface::class);
        $provider->method('provide')->willThrowException(new BadRequestException('Invalid argument "name".'));

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->never())->method('process');

        $handler = $this->createHandler($this->createOperationMetadataFactory($operation), $provider, $processor);

        $error = $handler->handle((new CallToolRequest('createDummy', []))->withId('req-1'), $this->createStub(SessionInterface::class));

        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame(Error::INTERNAL_ERROR, $error->code);
        $this->assertSame('Invalid argument "name".', $error->message);
        $this->assertSame('req-1', $error->id);
    }

    public function testHandleConvertsAProcessorHttpExceptionIntoAJsonRpcError(): void
    {
        $operation = new McpTool(name: 'createDummy', class: \stdClass::class);

        $provider = $this->createStub(ProviderInterface::class);
        $provider->method('provide')->willReturn(new \stdClass());

        $processor = $this->createStub(ProcessorInterface::class);
        $processor->method('process')->willThrowException(new BadRequestException('Cannot persist.'));

        $handler = $this->createHandler($this->createOperationMetadataFactory($operation), $provider, $processor);

        $error = $handler->handle((new CallToolRequest('createDummy', []))->withId(7), $this->createStub(SessionInterface::class));

        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame(Error::INTERNAL_ERROR, $error->code);
        $this->assertSame('Cannot persist.', $error->message);
        $this->assertSame(7, $error->id);
    }

    /**
     * Anything that is not caller-facing must stay uncaught, so that the SDK's own
     * handler answers it without leaking an arbitrary exception message.
     */
    public function testHandleLetsNonHttpExceptionsBubbleUp(): void
    {
        $operation = new McpTool(name: 'createDummy', class: \stdClass::class);

        $provider = $this->createStub(ProviderInterface::class);
        $provider->method('provide')->willThrowException(new \LogicException('Internal detail.'));

        $handler = $this->createHandler($this->createOperationMetadataFactory($operation), $provider);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Internal detail.');

        $handler->handle((new CallToolRequest('createDummy', []))->withId('req-1'), $this->createStub(SessionInterface::class));
    }

    public function testHandleHidesInternalSchemaValidationFailures(): void
    {
        $operation = new McpTool(name: 'createDummy', class: \stdClass::class);

        $schemaValidator = $this->createStub(SchemaValidator::class);
        $schemaValidator->method('validateAgainstJsonSchema')->willReturn([
            ['pointer' => '', 'keyword' => 'internal', 'message' => 'Schema validation process failed: secret'],
        ]);

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->never())->method('process');

        $handler = $this->createHandler($this->createOperationMetadataFactory($operation), processor: $processor, schemaValidator: $schemaValidator);

        $error = $handler->handle((new CallToolRequest('createDummy', []))->withId('req-1'), $this->createStub(SessionInterface::class));

        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame(Error::INTERNAL_ERROR, $error->code);
        $this->assertStringNotContainsString('secret', $error->message);
        $this->assertSame('req-1', $error->id);
    }

    private function createOperationMetadataFactory(?HttpOperation $operation): OperationMetadataFactoryInterface
    {
        $operationMetadataFactory = $this->createStub(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        return $operationMetadataFactory;
    }

    private function createHandler(
        OperationMetadataFactoryInterface $operationMetadataFactory,
        ?ProviderInterface $provider = null,
        ?ProcessorInterface $processor = null,
        ?RequestStack $requestStack = null,
        ?SchemaValidator $schemaValidator = null,
    ): Handler {
        $schemaFactory = $this->createStub(SchemaFactoryInterface::class);
        $schema = new Schema(Schema::VERSION_JSON_SCHEMA);
        $schema['type'] = 'object';
        $schemaFactory->method('buildSchema')->willReturn($schema);

        return new Handler(
            $operationMetadataFactory,
            $provider ?? $this->createStub(ProviderInterface::class),
            $processor ?? $this->createStub(ProcessorInterface::class),
            $requestStack ?? new RequestStack(),
            new ToolInputSchemaFactory($schemaFactory),
            schemaValidator: $schemaValidator,
        );
    }
}
