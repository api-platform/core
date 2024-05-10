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

use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Metadata\Util\CloneTrait;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RespondProcessor implements ProcessorInterface
{
    use ClassInfoTrait;
    use CloneTrait;

    public const METHOD_TO_CODE = [
        'POST' => Response::HTTP_CREATED,
        'DELETE' => Response::HTTP_NO_CONTENT,
    ];

    public function __construct(
        private ?IriConverterInterface $iriConverter = null,
        private readonly ?ResourceClassResolverInterface $resourceClassResolver = null,
        private readonly ?OperationMetadataFactoryInterface $operationMetadataFactory = null,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Response || !$operation instanceof HttpOperation) {
            return $data;
        }

        if (!($request = $context['request'] ?? null)) {
            return $data;
        }

        $headers = [
            'Content-Type' => sprintf('%s; charset=utf-8', $request->getMimeType($request->getRequestFormat())),
            'Vary' => 'Accept',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ];

        $exception = $request->attributes->get('exception');
        if (($exception instanceof HttpExceptionInterface || $exception instanceof SymfonyHttpExceptionInterface) && $exceptionHeaders = $exception->getHeaders()) {
            $headers = array_merge($headers, $exceptionHeaders);
        }

        if ($operationHeaders = $operation->getHeaders()) {
            $headers = array_merge($headers, $operationHeaders);
        }

        $status = $operation->getStatus();

        if ($sunset = $operation->getSunset()) {
            $headers['Sunset'] = (new \DateTimeImmutable($sunset))->format(\DateTimeInterface::RFC1123);
        }

        if ($acceptPatch = $operation->getAcceptPatch()) {
            $headers['Accept-Patch'] = $acceptPatch;
        }

        $method = $request->getMethod();
        $originalData = $context['original_data'] ?? null;

        $outputMetadata = $operation->getOutput() ?? ['class' => $operation->getClass()];
        $hasOutput = \is_array($outputMetadata) && \array_key_exists('class', $outputMetadata) && null !== $outputMetadata['class'];
        $hasData = !$hasOutput ? false : ($this->resourceClassResolver && $originalData && \is_object($originalData) && $this->resourceClassResolver->isResourceClass($this->getObjectClass($originalData)));

        if ($hasData && $this->iriConverter) {
            if (
                !isset($headers['Location'])
                && 300 <= $status && $status < 400
                && (($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false) || ($operation->getExtraProperties()['canonical_uri_template'] ?? null))
            ) {
                $canonicalOperation = $operation;
                if ($this->operationMetadataFactory && null !== ($operation->getExtraProperties()['canonical_uri_template'] ?? null)) {
                    $canonicalOperation = $this->operationMetadataFactory->create($operation->getExtraProperties()['canonical_uri_template'], $context);
                }

                $headers['Location'] = $this->iriConverter->getIriFromResource($originalData, UrlGeneratorInterface::ABS_PATH, $canonicalOperation);
            } elseif ('PUT' === $method && !$request->attributes->get('previous_data') && null === $status && ($operation instanceof Put && ($operation->getAllowCreate() ?? false))) {
                $status = 201;
            }
        }

        $status ??= self::METHOD_TO_CODE[$method] ?? 200;

        if ($hasData && $this->iriConverter && !isset($headers['Content-Location'])) {
            $iri = $this->iriConverter->getIriFromResource($originalData);
            $headers['Content-Location'] = $iri;

            if ((201 === $status || (300 <= $status && $status < 400)) && 'POST' === $method && !isset($headers['Location'])) {
                $headers['Location'] = $iri;
            }
        }

        return new Response(
            $data,
            $status,
            $headers
        );
    }
}
