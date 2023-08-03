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

namespace ApiPlatform\Action;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\ErrorFormatGuesser;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Renders a normalized exception for a given see [FlattenException](https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php).
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ExceptionAction
{
    use OperationRequestInitiatorTrait;

    /**
     * @param array $errorFormats      A list of enabled error formats
     * @param array $exceptionToStatus A list of exceptions mapped to their HTTP status code
     */
    public function __construct(private readonly SerializerInterface $serializer, private readonly array $errorFormats, private readonly array $exceptionToStatus = [], ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * Converts an exception to a JSON response.
     */
    public function __invoke(FlattenException $exception, Request $request): Response
    {
        $operation = $this->initializeOperation($request);
        $exceptionClass = $exception->getClass();
        $statusCode = $exception->getStatusCode();

        $exceptionToStatus = array_merge(
            $this->exceptionToStatus,
            $operation ? $operation->getExceptionToStatus() ?? [] : $this->getOperationExceptionToStatus($request)
        );

        foreach ($exceptionToStatus as $class => $status) {
            if (is_a($exceptionClass, $class, true)) {
                $statusCode = $status;

                break;
            }
        }

        $headers = $exception->getHeaders();
        $format = ErrorFormatGuesser::guessErrorFormat($request, $this->errorFormats);
        $headers['Content-Type'] = sprintf('%s; charset=utf-8', $format['value'][0]);
        $headers['X-Content-Type-Options'] = 'nosniff';
        $headers['X-Frame-Options'] = 'deny';

        return new Response($this->serializer->serialize($exception, $format['key'], ['statusCode' => $statusCode]), $statusCode, $headers);
    }

    private function getOperationExceptionToStatus(Request $request): array
    {
        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if ([] === $attributes) {
            return [];
        }

        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($attributes['resource_class']);
        /** @var HttpOperation $operation */
        $operation = $resourceMetadataCollection->getOperation($attributes['operation_name'] ?? null);
        $exceptionToStatus = [$operation->getExceptionToStatus() ?: []];

        foreach ($resourceMetadataCollection as $resourceMetadata) {
            /* @var ApiResource $resourceMetadata */
            $exceptionToStatus[] = $resourceMetadata->getExceptionToStatus() ?: [];
        }

        return array_merge(...$exceptionToStatus);
    }
}
