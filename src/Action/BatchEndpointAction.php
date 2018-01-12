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

namespace ApiPlatform\Core\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides an endpoint for batch processing by splitting up
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class BatchEndpointAction
{
    /**
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * @var array
     */
    private $validKeys = [
        'path',
        'method',
        'headers',
        'body',
    ];

    /**
     * @param HttpKernelInterface $kernel
     */
    public function __construct(HttpKernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Execute multiple subrequests from batch request.
     *
     * @param Request $request
     * @param array   $data
     *
     * @return array
     */
    public function __invoke(Request $request, array $data = null): array
    {
        if (!$this->validateBatchData((array) $data)) {
            throw new BadRequestHttpException('Batch request data not accepted.');
        }

        $result = [];

        foreach ($data as $k => $item) {

            // Copy current headers if no specific provided for simplicity
            // otherwise one would have to provide Content-Type with every
            // single entry.
            if (!isset($item['headers'])) {
                $item['headers'] = $request->headers->all();
            }

            $result[] = $this->convertResponse(
                $this->executeSubRequest(
                    $k,
                    $item['path'],
                    $item['method'],
                    $item['headers'],
                    $item['body']
                )
            );
        }

        return $result;
    }

    /**
     * Converts a response into an array.
     *
     * @param Response $response
     *
     * @return array
     */
    private function convertResponse(Response $response): array
    {
        return [
            'status' => $response->getStatusCode(),
            'body' => $response->getContent(),
            'headers' => $response->headers->all(),
        ];
    }

    /**
     * Validates that the keys are all correctly present.
     *
     * @param array $data
     *
     * @return bool
     */
    private function validateBatchData(array $data)
    {
        if (0 === count($data)) {
            return false;
        }

        foreach ($data as $item) {
            if (0 !== count(array_diff(array_keys($item), $this->validKeys))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Executes a subrequest.
     *
     * @param int    $index
     * @param string $path
     * @param string $method
     * @param array  $headers
     * @param string $body
     *
     * @return Response
     */
    private function executeSubRequest(int $index, string $path, string $method, array $headers, string $body): Response
    {
        $subRequest = Request::create($path, $method, [], [], [], [], $body);
        $subRequest->headers->replace($headers);

        try {
            return $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        } catch (\Exception $e) {
            return Response::create(sprintf('Batch element #%d failed, check the log files.', $index), 400);
        }
    }
}
