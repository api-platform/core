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

namespace ApiPlatform\Mcp\Server\Handler\Request;

use ApiPlatform\Mcp\Schema\Result\StructuredContentResult;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\ReadResourceRequest;
use Mcp\Schema\Result\ReadResourceResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ReadResourceHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly ?LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request instanceof ReadResourceRequest;
    }

    public function handle(Request $request, SessionInterface $session): Response|Error
    {
        \assert($request instanceof ReadResourceRequest);

        $uri = $request->uri;

        $this->logger->debug('Reading resource', ['uri' => $uri]);

        try {
            $httpRequest = HttpRequest::create($uri, 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/ld+json']);
            $response = $this->kernel->handle($httpRequest, HttpKernelInterface::SUB_REQUEST);
            if (!$content = $response->getContent()) {
                throw new RuntimeException('No content');
            }

            $array = json_decode($content, true);

            return new Response($request->getId(), new StructuredContentResult($array, new ReadResourceResult([new TextResourceContents($array['@id'], 'application/ld+json', $content)])));
        } catch (OperationNotFoundException $e) {
            $this->logger->error('Resource not found', ['uri' => $uri]);

            return new Error($request->getId(), Error::RESOURCE_NOT_FOUND, $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error(\sprintf('Error while reading resource "%s": "%s".', $uri, $e->getMessage()));

            return Error::forInternalError('Error while reading resource', $request->getId());
        }
    }
}
