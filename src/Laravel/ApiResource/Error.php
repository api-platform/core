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

namespace ApiPlatform\Laravel\ApiResource;

use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Error as Operation;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\WebLink\Link;

#[ErrorResource(
    types: ['hydra:Error'],
    openapi: false,
    operations: [
        new Operation(
            name: '_api_errors_problem',
            outputFormats: ['json' => ['application/problem+json']],
            normalizationContext: [
                'groups' => ['jsonproblem'],
                'skip_null_values' => true,
            ],
            uriTemplate: '/errors/{status}'
        ),
        new Operation(
            name: '_api_errors_hydra',
            outputFormats: ['jsonld' => ['application/problem+json']],
            normalizationContext: [
                'groups' => ['jsonld'],
                'skip_null_values' => true,
            ],
            links: [new Link(rel: ContextBuilderInterface::JSONLD_NS.'error', href: 'http://www.w3.org/ns/hydra/error')],
            uriTemplate: '/hydra_errors/{status}'
        ),
        new Operation(
            name: '_api_errors_jsonapi',
            outputFormats: ['jsonapi' => ['application/vnd.api+json']],
            normalizationContext: ['groups' => ['jsonapi'], 'skip_null_values' => true],
            uriTemplate: '/jsonapi_errors/{status}'
        ),
    ],
    graphQlOperations: []
)]
class Error extends \Exception implements ProblemExceptionInterface, HttpExceptionInterface
{
    public function __construct(
        private readonly string $title,
        private readonly string $detail,
        #[ApiProperty(identifier: true)] private int $status,
        private readonly array $originalTrace,
        private ?string $instance = null,
        private string $type = 'about:blank',
        private array $headers = []
    ) {
        parent::__construct();
    }

    #[SerializedName('hydra:title')]
    #[Groups(['jsonld'])]
    public function getHydraTitle(): string
    {
        return $this->title;
    }

    #[SerializedName('trace')]
    #[Groups(['trace'])]
    public function getOriginalTrace(): array
    {
        return $this->originalTrace;
    }

    #[SerializedName('hydra:description')]
    #[Groups(['jsonld'])]
    public function getHydraDescription(): string
    {
        return $this->detail;
    }

    #[SerializedName('description')]
    #[Groups(['jsonapi'])]
    public function getDescription(): string
    {
        return $this->detail;
    }

    public static function createFromException(\Exception|\Throwable $exception, int $status): self
    {
        $headers = ($exception instanceof SymfonyHttpExceptionInterface || $exception instanceof HttpExceptionInterface) ? $exception->getHeaders() : [];

        return new self('An error occurred', $exception->getMessage(), $status, $exception->getTrace(), type: '/errors/'.$status, headers: $headers);
    }

    #[Ignore]
    public function getHeaders(): array
    {
        return $this->headers;
    }

    #[Ignore]
    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    #[Groups(['jsonld', 'jsonproblem'])]
    public function getType(): string
    {
        return $this->type;
    }

    #[Groups(['jsonld', 'jsonproblem', 'jsonapi'])]
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    #[Groups(['jsonld', 'jsonproblem'])]
    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    #[Groups(['jsonld', 'jsonproblem'])]
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    #[Groups(['jsonld', 'jsonproblem'])]
    public function getInstance(): ?string
    {
        return $this->instance;
    }
}
