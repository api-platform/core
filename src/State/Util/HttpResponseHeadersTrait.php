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

namespace ApiPlatform\State\Util;

use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Metadata\Util\CloneTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;

/**
 * Shares the logic to create API Platform's headers.
 *
 * @internal
 */
trait HttpResponseHeadersTrait
{
    use ClassInfoTrait;
    use CloneTrait;
    private ?IriConverterInterface $iriConverter;
    private ?OperationMetadataFactoryInterface $operationMetadataFactory;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, string|string[]>
     */
    private function getHeaders(Request $request, HttpOperation $operation, array $context): array
    {
        $status = $this->getStatus($request, $operation, $context);
        $headers = [
            'Content-Type' => \sprintf('%s; charset=utf-8', $request->getMimeType($request->getRequestFormat())),
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

        if ($hasData) {
            $isAlternateResourceMetadata = $operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false;
            $canonicalUriTemplate = $operation->getExtraProperties()['canonical_uri_template'] ?? null;

            if (
                !isset($headers['Location'])
                && 300 <= $status && $status < 400
                && ($isAlternateResourceMetadata || $canonicalUriTemplate)
            ) {
                $canonicalOperation = $operation;
                if ($this->operationMetadataFactory && null !== $canonicalUriTemplate) {
                    $canonicalOperation = $this->operationMetadataFactory->create($canonicalUriTemplate, $context);
                }

                if ($this->iriConverter) {
                    $headers['Location'] = $this->iriConverter->getIriFromResource($originalData, UrlGeneratorInterface::ABS_PATH, $canonicalOperation);
                }
            }
        }

        $requestParts = parse_url($request->getRequestUri());
        if ($this->iriConverter && !isset($headers['Content-Location'])) {
            try {
                $iri = null;
                if ($hasData) {
                    $iri = $this->iriConverter->getIriFromResource($originalData);
                } elseif ($operation->getClass()) {
                    $iri = $this->iriConverter->getIriFromResource($operation->getClass(), UrlGeneratorInterface::ABS_PATH, $operation);
                }

                if ($iri && 'GET' !== $method) {
                    $location = \sprintf('%s.%s', $iri, $request->getRequestFormat());
                    if (isset($requestParts['query'])) {
                        $location .= '?'.$requestParts['query'];
                    }

                    $headers['Content-Location'] = $location;
                    if ((Response::HTTP_CREATED === $status || (300 <= $status && $status < 400)) && 'POST' === $method && !isset($headers['Location'])) {
                        $headers['Location'] = $iri;
                    }
                }
            } catch (InvalidArgumentException|ItemNotFoundException|RuntimeException) {
            }
        }

        return $headers;
    }
}
