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

namespace ApiPlatform\Mcp\Tests\State;

use ApiPlatform\Mcp\State\StructuredContentProcessor;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Result\ReadResourceResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @see https://github.com/api-platform/core/issues/8267
 */
class StructuredContentProcessorTest extends TestCase
{
    /**
     * When structuredContent is disabled, the payload must still be serialized
     * into the mandatory TextContent: only the extra structuredContent field
     * should be omitted, not the human-readable text.
     */
    public function testTextContentIsPopulatedWhenStructuredContentIsDisabled(): void
    {
        // The MCP Handler disables serialization, so the decorated processor
        // returns a non-string collection object (here a Paginator-like object
        // with no public/JsonSerializable state, which json_encodes to "{}").
        $collection = new \stdClass();

        $expectedJson = '{"@context":"\/contexts\/Dummy","@type":"hydra:Collection","hydra:member":[{"name":"foo"}]}';

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($collection);

        $serializer = $this->createMock(SerializerEncoderNormalizer::class);
        $serializer->method('normalize')->willReturn(['hydra:member' => [['name' => 'foo']]]);
        $serializer->method('encode')->willReturn($expectedJson);

        $contextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $contextBuilder->method('createFromRequest')->willReturn([]);

        $processor = new StructuredContentProcessor($serializer, $contextBuilder, $decorated);

        $operation = (new McpTool())->withStructuredContent(false)->withClass(\stdClass::class);

        $mcpRequest = $this->createMock(Request::class);
        $mcpRequest->method('getId')->willReturn('req-1');

        /** @var Response $response */
        $response = $processor->process([], $operation, [], [
            'mcp_request' => $mcpRequest,
            'request' => new HttpRequest(),
        ]);

        $result = $response->result;
        $this->assertInstanceOf(CallToolResult::class, $result);

        // structuredContent flag disabled -> extra field must be absent.
        $this->assertNull($result->structuredContent);

        // ...but the mandatory text content must still carry the real payload.
        $textContent = $result->content[0];
        $this->assertInstanceOf(TextContent::class, $textContent);
        $this->assertNotSame('{}', $textContent->text);
        $this->assertSame($expectedJson, $textContent->text);
    }

    public function testMcpResourceReturnsReadResourceResult(): void
    {
        $expectedJson = '{"name":"foo"}';
        $resourceUri = 'app://dummy';
        $resourceMimeType = 'application/json';

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn(new \stdClass());

        $serializer = $this->createMock(SerializerEncoderNormalizer::class);
        $serializer->method('normalize')->willReturn(['name' => 'foo']);
        $serializer->method('encode')->willReturn($expectedJson);

        $contextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $contextBuilder->method('createFromRequest')->willReturn([]);

        $processor = new StructuredContentProcessor($serializer, $contextBuilder, $decorated);

        $operation = (new McpResource(uri: $resourceUri, mimeType: $resourceMimeType))->withClass(\stdClass::class);

        $mcpRequest = $this->createMock(Request::class);
        $mcpRequest->method('getId')->willReturn('req-1');

        /** @var Response $response */
        $response = $processor->process([], $operation, [], [
            'mcp_request' => $mcpRequest,
            'request' => new HttpRequest(),
        ]);

        $result = $response->result;
        $this->assertInstanceOf(ReadResourceResult::class, $result);

        $resourceContents = $result->contents[0];
        $this->assertInstanceOf(TextResourceContents::class, $resourceContents);
        $this->assertSame($resourceUri, $resourceContents->uri);
        $this->assertSame($resourceMimeType, $resourceContents->mimeType);
        $this->assertSame($expectedJson, $resourceContents->text);
    }
}

/**
 * Helper interface so the mocked serializer satisfies the
 * `instanceof NormalizerInterface && instanceof EncoderInterface` guard.
 */
interface SerializerEncoderNormalizer extends SerializerInterface, NormalizerInterface, EncoderInterface
{
}
