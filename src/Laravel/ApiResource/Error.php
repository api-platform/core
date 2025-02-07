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

use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Error as Operation;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\ErrorResourceInterface;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\State\ErrorProvider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\WebLink\Link;

#[ErrorResource(
    uriTemplate: '/errors/{status}{._format}',
    openapi: false,
    uriVariables: ['status'],
    operations: [
        new Operation(
            errors: [],
            name: '_api_errors_problem',
            routeName: '_api_errors',
            outputFormats: ['json' => ['application/problem+json', 'application/json']],
            hideHydraOperation: true,
            normalizationContext: [
                SchemaFactory::OPENAPI_DEFINITION_NAME => '',
                'groups' => ['jsonproblem'],
                'skip_null_values' => true,
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
            ],
        ),
        new Operation(
            errors: [],
            name: '_api_errors_hydra',
            routeName: '_api_errors',
            outputFormats: ['jsonld' => ['application/problem+json', 'application/ld+json']],
            normalizationContext: [
                SchemaFactory::OPENAPI_DEFINITION_NAME => '',
                'groups' => ['jsonld'],
                'skip_null_values' => true,
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
            ],
            links: [new Link(rel: 'http://www.w3.org/ns/json-ld#error', href: 'http://www.w3.org/ns/hydra/error')],
        ),
        new Operation(
            errors: [],
            name: '_api_errors_jsonapi',
            routeName: '_api_errors',
            hideHydraOperation: true,
            outputFormats: ['jsonapi' => ['application/vnd.api+json']],
            normalizationContext: [
                SchemaFactory::OPENAPI_DEFINITION_NAME => '',
                'disable_json_schema_serializer_groups' => false,
                'groups' => ['jsonapi'],
                'skip_null_values' => true,
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
            ],
        ),
        new Operation(
            name: '_api_errors',
            hideHydraOperation: true,
            extraProperties: ['_api_disable_swagger_provider' => true],
            outputFormats: ['html' => ['text/html'], 'jsonapi' => ['application/vnd.api+json'], 'jsonld' => ['application/ld+json'], 'json' => ['application/problem+json', 'application/json']],
        ),
    ],
    outputFormats: ['jsonapi' => ['application/vnd.api+json'], 'jsonld' => ['application/ld+json'], 'json' => ['application/problem+json', 'application/json']],
    provider: ErrorProvider::class,
    graphQlOperations: [],
    description: 'A representation of common errors.',
)]
#[ApiProperty(property: 'previous', hydra: false, readable: false)]
#[ApiProperty(property: 'traceAsString', hydra: false, readable: false)]
#[ApiProperty(property: 'string', hydra: false, readable: false)]
class Error extends \Exception implements ProblemExceptionInterface, HttpExceptionInterface, ErrorResourceInterface
{
    /**
     * @var array<int, mixed>
     */
    private array $originalTrace;

    /**
     * @param array<string, string> $headers
     * @param array<int, mixed>     $originalTrace
     */
    public function __construct(
        private readonly string $title,
        private readonly string $detail,
        #[ApiProperty(identifier: true)] private int $status,
        array $originalTrace = [],
        private readonly ?string $instance = null,
        private string $type = 'about:blank',
        private array $headers = [],
    ) {
        parent::__construct();

        $this->originalTrace = [];
        foreach ($originalTrace as $i => $t) {
            unset($t['args']); // we don't want arguments in our JSON traces, especially with xdebug
            $this->originalTrace[$i] = $t;
        }
    }

    /**
     * @return array<int, mixed>
     */
    #[SerializedName('trace')]
    #[Groups(['trace'])]
    public function getOriginalTrace(): array
    {
        return $this->originalTrace;
    }

    #[SerializedName('description')]
    public function getDescription(): string
    {
        return $this->detail;
    }

    public static function createFromException(\Exception|\Throwable $exception, int $status): self
    {
        $headers = ($exception instanceof SymfonyHttpExceptionInterface || $exception instanceof HttpExceptionInterface) ? $exception->getHeaders() : [];

        return new self('An error occurred', $exception->getMessage(), $status, $exception->getTrace(), type: '/errors/'.$status, headers: $headers);
    }

    /**
     * @return array<string, string>
     */
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

    #[Groups(['jsonapi'])]
    public function getId(): string
    {
        return (string) $this->status;
    }

    /**
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    #[Groups(['jsonld', 'jsonproblem', 'jsonapi'])]
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

    #[Groups(['jsonld', 'jsonproblem', 'jsonapi'])]
    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    #[Groups(['jsonld', 'jsonproblem', 'jsonapi'])]
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    #[Groups(['jsonld', 'jsonproblem', 'jsonapi'])]
    public function getInstance(): ?string
    {
        return $this->instance;
    }
}
