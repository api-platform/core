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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ContentNegotiationTrait;
use ApiPlatform\State\ProviderInterface;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

final class ContentNegotiationProvider implements ProviderInterface
{
    use ContentNegotiationTrait;

    /**
     * @param array<string, string[]> $formats
     * @param array<string, string[]> $errorFormats
     */
    public function __construct(private readonly ?ProviderInterface $decorated = null, ?Negotiator $negotiator = null, private readonly array $formats = [], private readonly array $errorFormats = [])
    {
        $this->negotiator = $negotiator ?? new Negotiator();
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!($request = $context['request'] ?? null) || !$operation instanceof HttpOperation) {
            return $this->decorated?->provide($operation, $uriVariables, $context);
        }

        $isErrorOperation = $operation instanceof ErrorOperation;

        $formats = $operation->getOutputFormats() ?? ($isErrorOperation ? $this->errorFormats : $this->formats);
        $this->addRequestFormats($request, $formats);
        $request->attributes->set('input_format', $this->getInputFormat($operation, $request));
        $request->setRequestFormat($this->getRequestFormat($request, $formats, !$isErrorOperation));

        return $this->decorated?->provide($operation, $uriVariables, $context);
    }

    /**
     * Adds the supported formats to the request.
     *
     * This is necessary for {@see Request::getMimeType} and {@see Request::getMimeTypes} to work.
     * Note that this replaces default mime types configured at {@see Request::initializeFormats}
     *
     * @param array<string, string|string[]> $formats
     */
    private function addRequestFormats(Request $request, array $formats): void
    {
        foreach ($formats as $format => $mimeTypes) {
            $request->setFormat($format, (array) $mimeTypes);
        }
    }

    /**
     * Flattened the list of MIME types.
     *
     * @param array<string, string|string[]> $formats
     *
     * @return array<string, string>
     */
    private function flattenMimeTypes(array $formats): array
    {
        $flattenedMimeTypes = [];
        foreach ($formats as $format => $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $flattenedMimeTypes[$mimeType] = $format;
            }
        }

        return $flattenedMimeTypes;
    }

    /**
     * Extracts the format from the Content-Type header and check that it is supported.
     *
     * @throws UnsupportedMediaTypeHttpException
     */
    private function getInputFormat(HttpOperation $operation, Request $request): ?string
    {
        if (
            false === ($input = $operation->getInput())
            || (\is_array($input) && null === $input['class'])
            || false === $operation->canDeserialize()
        ) {
            return null;
        }

        $contentType = $request->headers->get('CONTENT_TYPE');
        if (null === $contentType || '' === $contentType) {
            return null;
        }

        /** @var string $contentType */
        $formats = $operation->getInputFormats() ?? [];
        if ($format = $this->getMimeTypeFormat($contentType, $formats)) {
            return $format;
        }

        if (!$request->isMethodSafe() && 'DELETE' !== $request->getMethod()) {
            $supportedMimeTypes = [];
            foreach ($formats as $mimeTypes) {
                foreach ($mimeTypes as $mimeType) {
                    $supportedMimeTypes[] = $mimeType;
                }
            }

            throw new UnsupportedMediaTypeHttpException(\sprintf('The content-type "%s" is not supported. Supported MIME types are "%s".', $contentType, implode('", "', $supportedMimeTypes)));
        }

        return null;
    }
}
