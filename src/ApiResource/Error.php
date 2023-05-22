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

namespace ApiPlatform\ApiResource;

use ApiPlatform\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Get;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ErrorResource(
    uriTemplate: '/errors/{status}',
    provider: 'api_platform.state_provider.default_error',
    types: ['hydra:Error'],
    operations: [
        new Get(name: '_api_errors_hydra', outputFormats: ['jsonld' => ['application/ld+json']], normalizationContext: ['groups' => ['jsonld'], 'skip_null_values' => true]),
        new Get(name: '_api_errors_problem', outputFormats: ['json' => ['application/problem+json']], normalizationContext: ['groups' => ['jsonproblem'], 'skip_null_values' => true]),
        new Get(name: '_api_errors_jsonapi', outputFormats: ['jsonapi' => ['application/vnd.api+json']], normalizationContext: ['groups' => ['jsonapi'], 'skip_null_values' => true], provider: 'api_platform.state_provider.json_api.default_error'),
    ]
)]
class Error extends \Exception implements ProblemExceptionInterface, HttpExceptionInterface
{
    public function __construct(
        private readonly string $title,
        private readonly string $detail,
        #[ApiProperty(identifier: true)] private readonly int $status,
        #[Groups(['trace'])]
        public readonly array $trace,
        private ?string $instance = null,
        private string $type = 'about:blank',
        private array $headers = []
    ) {
        parent::__construct();
    }

    #[SerializedName('hydra:title')]
    #[Groups(['jsonld', 'legacy_jsonld'])]
    public function getHydraTitle(): string
    {
        return $this->title;
    }

    #[SerializedName('hydra:description')]
    #[Groups(['jsonld', 'legacy_jsonld'])]
    public function getHydraDescription(): string
    {
        return $this->detail;
    }

    #[SerializedName('description')]
    #[Groups(['jsonapi', 'legacy_jsonapi'])]
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

    #[Groups(['jsonld', 'legacy_jsonproblem', 'jsonproblem', 'jsonapi', 'legacy_jsonapi'])]
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    #[Groups(['jsonld', 'jsonproblem', 'legacy_jsonproblem'])]
    public function getStatus(): ?int
    {
        return $this->status;
    }

    #[Groups(['jsonld', 'jsonproblem', 'legacy_jsonproblem'])]
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    #[Groups(['jsonld', 'jsonproblem', 'legacy_jsonproblem'])]
    public function getInstance(): ?string
    {
        return $this->instance;
    }
}
