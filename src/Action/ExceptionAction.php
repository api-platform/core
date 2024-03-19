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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\ErrorFormatGuesser;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\Debug\Exception\FlattenException as LegacyFlattenException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Renders a normalized exception for a given {@see FlattenException} or {@see LegacyFlattenException}.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ExceptionAction
{
    use OperationRequestInitiatorTrait;

    private $serializer;
    private $errorFormats;
    private $exceptionToStatus;
    /**
     * @var ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface|null
     */
    private $resourceMetadataFactory;

    /**
     * @param array      $errorFormats            A list of enabled error formats
     * @param array      $exceptionToStatus       A list of exceptions mapped to their HTTP status code
     * @param mixed|null $resourceMetadataFactory
     */
    public function __construct(SerializerInterface $serializer, array $errorFormats, array $exceptionToStatus = [], $resourceMetadataFactory = null)
    {
        $this->serializer = $serializer;
        $this->errorFormats = $errorFormats;
        $this->exceptionToStatus = $exceptionToStatus;
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if (null !== $resourceMetadataFactory && !$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        } else {
            $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        }
    }

    /**
     * Converts an exception to a JSON response.
     *
     * @param FlattenException|LegacyFlattenException $exception
     */
    public function __invoke($exception, Request $request): Response
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
        // TODO: remove legacy layer in 3.0
        if ($request->attributes->has('_api_exception_to_status')) {
            return $request->attributes->get('_api_exception_to_status');
        }

        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if ([] === $attributes || null === $this->resourceMetadataFactory) {
            return [];
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $operationExceptionToStatus = $resourceMetadata->getOperationAttribute($attributes, 'exception_to_status', [], false);
        $resourceExceptionToStatus = $resourceMetadata->getAttribute('exception_to_status', []);

        if (!\is_array($operationExceptionToStatus) || !\is_array($resourceExceptionToStatus)) {
            throw new \LogicException('"exception_to_status" attribute should be an array.');
        }

        return array_merge(
            $resourceExceptionToStatus,
            $operationExceptionToStatus
        );
    }
}

class_alias(ExceptionAction::class, \ApiPlatform\Core\Action\ExceptionAction::class);
