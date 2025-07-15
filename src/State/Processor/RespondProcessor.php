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

namespace ApiPlatform\State\Processor;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Util\HttpResponseHeadersTrait;
use ApiPlatform\State\Util\HttpResponseStatusTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RespondProcessor implements ProcessorInterface
{
    use HttpResponseHeadersTrait;
    use HttpResponseStatusTrait;

    public function __construct(
        ?IriConverterInterface $iriConverter = null,
        ?ResourceClassResolverInterface $resourceClassResolver = null,
        ?OperationMetadataFactoryInterface $operationMetadataFactory = null,
    ) {
        $this->iriConverter = $iriConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMetadataFactory = $operationMetadataFactory;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Response || !$operation instanceof HttpOperation) {
            return $data;
        }

        if (!($request = $context['request'] ?? null)) {
            return $data;
        }

        return new Response(
            $data,
            $this->getStatus($request, $operation, $context),
            $this->getHeaders($request, $operation, $context)
        );
    }
}
