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

namespace ApiPlatform\Serializer\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Util\HttpResponseHeadersTrait;
use ApiPlatform\State\Util\HttpResponseStatusTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\JsonStreamer\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * @implements ProcessorInterface<mixed,mixed>
 */
final class JsonStreamerProcessor implements ProcessorInterface
{
    use HttpResponseHeadersTrait;
    use HttpResponseStatusTrait;

    /**
     * @param ProcessorInterface<mixed,mixed>|null       $processor
     * @param StreamWriterInterface<array<string,mixed>> $jsonStreamer
     */
    public function __construct(
        private readonly ?ProcessorInterface $processor,
        private readonly StreamWriterInterface $jsonStreamer,
        ?IriConverterInterface $iriConverter = null,
        ?ResourceClassResolverInterface $resourceClassResolver = null,
        ?OperationMetadataFactoryInterface $operationMetadataFactory = null,
    ) {
        $this->resourceClassResolver = $resourceClassResolver;
        $this->iriConverter = $iriConverter;
        $this->operationMetadataFactory = $operationMetadataFactory;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (
            $operation instanceof Error
            || $data instanceof Response
            || !$operation instanceof HttpOperation
            || !($request = $context['request'] ?? null)
            || !$operation->getJsonStream()
            || 'json' !== $request->getRequestFormat()
        ) {
            return $this->processor?->process($data, $operation, $uriVariables, $context);
        }

        if ($operation instanceof CollectionOperationInterface) {
            $data = $this->jsonStreamer->write(
                $data,
                Type::list(Type::object($operation->getClass())),
                ['data' => $data, 'operation' => $operation],
            );
        } else {
            $data = $this->jsonStreamer->write(
                $data,
                Type::object($operation->getClass()),
                ['data' => $data, 'operation' => $operation],
            );
        }

        /** @var iterable<string> $data */
        $response = new StreamedResponse(
            $data,
            $this->getStatus($request, $operation, $context),
            $this->getHeaders($request, $operation, $context)
        );

        return $this->processor ? $this->processor->process($response, $operation, $uriVariables, $context) : $response;
    }
}
