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

namespace ApiPlatform\State;

use ApiPlatform\Metadata\ErrorResourceInterface;
use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ApiResource\Error;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
final class ErrorProvider implements ProviderInterface
{
    public function __construct(private readonly bool $debug = false, private ?ResourceClassResolverInterface $resourceClassResolver = null, private ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null)
    {
    }

    /**
     * @param array{status?: int} $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        if (!($request = $context['request'] ?? null) || !$operation instanceof HttpOperation) {
            throw new \RuntimeException('Not an HTTP request');
        }

        if (!($exception = $request->attributes->get('exception'))) {
            $status = $uriVariables['status'] ?? null;

            // We change the operation to get our normalization context according to the format
            if ($this->resourceMetadataCollectionFactory) {
                $resourceCollection = $this->resourceMetadataCollectionFactory->create($operation->getClass());
                foreach ($resourceCollection as $resource) {
                    foreach ($resource->getOperations() as $name => $operation) {
                        if (isset($operation->getOutputFormats()[$request->getRequestFormat()])) {
                            $request->attributes->set('_api_operation', $operation);
                            $request->attributes->set('_api_operation_name', $name);
                            break 2;
                        }
                    }
                }
            }

            $text = Response::$statusTexts[$status] ?? throw new NotFoundHttpException();

            $cl = $operation->getClass();

            return match ($request->getRequestFormat()) {
                'html' => $this->renderError((int) $status, $text),
                default => new $cl("Error $status", $text, (int) $status),
            };
        }

        if ($this->resourceClassResolver?->isResourceClass($exception::class)) {
            return $exception;
        }

        $status = $operation->getStatus() ?? 500;
        $cl = is_a($operation->getClass(), ErrorResourceInterface::class, true) ? $operation->getClass() : Error::class;
        $error = $cl::createFromException($exception, $status);
        if (null !== ($accessDeniedException = $this->findAccessDeniedException($exception))) {
            if ($status !== $accessDeniedException->getStatus()) {
                if (method_exists($error, 'setStatus')) {
                    $error->setStatus($status);
                }
                if (method_exists($error, 'setType')) {
                    $error->setType("/errors/$status");
                }
            }

            if (method_exists($error, 'setDetail')) {
                $error->setDetail($accessDeniedException->getDetail() ?? ($this->debug ? $accessDeniedException->getMessage() : 'Access Denied.'));
            }
        }
        if (!$this->debug && $status >= 500 && method_exists($error, 'setDetail')) {
            $error->setDetail('Internal Server Error');
        }

        return $error;
    }

    private function renderError(int $status, string $text): Response
    {
        return new Response(<<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Error $status</title>
    </head>
    <body><h1>Error $status</h1>$text</body>
</html>
HTML);
    }

    private function findAccessDeniedException(\Throwable $exception): ?AccessDeniedException
    {
        $current = $exception;
        while (null !== $current) {
            if ($current instanceof AccessDeniedException) {
                return $current;
            }

            $current = $current->getPrevious();
        }

        return null;
    }
}
