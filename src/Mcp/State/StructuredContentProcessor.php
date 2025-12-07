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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Result\CallToolResult;
use Symfony\Component\Serializer\Encoder\ContextAwareEncoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

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

        if ($data instanceof CallToolResult) {
            return new Response($context['mcp_request']->getId(), $data);
        }

        $context['original_data'] = $data;
        $class = $operation->getClass();
        $serializerContext = $this->serializerContextBuilder->createFromRequest($request, true, [
            'resource_class' => $class,
            'operation' => $operation,
        ]);

        $serializerContext['uri_variables'] = $uriVariables;

        $structuredContent = $this->serializer->normalize($data, $format = $request->getRequestFormat(), $serializerContext);

        return new Response(
                $context['mcp_request']->getId(),
                new CallToolResult(
                    [new TextContent($this->serializer->encode($structuredContent, $format, $serializerContext))],
                    false,
                    $structuredContent,
                ),
            );
    }
}
