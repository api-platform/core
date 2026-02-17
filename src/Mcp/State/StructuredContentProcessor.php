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

namespace ApiPlatform\Mcp\State;

use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Result\ReadResourceResult;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @experimental
 */
final class StructuredContentProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly SerializerContextBuilderInterface $serializerContextBuilder,
        public readonly ProcessorInterface $decorated,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (
            !$this->serializer instanceof NormalizerInterface
            || !$this->serializer instanceof EncoderInterface
            || !isset($context['mcp_request'])
            || !($request = $context['request'])
        ) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $result = $this->decorated->process($data, $operation, $uriVariables, $context);

        if ($result instanceof CallToolResult || $result instanceof ReadResourceResult) {
            return new Response($context['mcp_request']->getId(), $result);
        }

        $context['original_data'] = $result;
        $class = $operation->getClass();
        $includeStructuredContent = ($operation instanceof McpTool || $operation instanceof McpResource) && ($operation->getStructuredContent() ?? true);
        $structuredContent = null;

        if ($includeStructuredContent) {
            $serializerContext = $this->serializerContextBuilder->createFromRequest($request, true, [
                'resource_class' => $class,
                'operation' => $operation,
            ]);
            $serializerContext['uri_variables'] = $uriVariables;
            $format = $request->getRequestFormat('') ?: 'jsonld';
            $structuredContent = $this->serializer->normalize($result, $format, $serializerContext);
            $result = $this->serializer->encode($structuredContent, $format, $serializerContext);
        }

        return new Response(
            $context['mcp_request']->getId(),
            new CallToolResult(
                [new TextContent($result)],
                false,
                $structuredContent
            ),
        );
    }
}
