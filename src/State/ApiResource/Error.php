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

namespace ApiPlatform\State\ApiResource;

use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Error as Operation;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\ErrorResourceInterface;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\WebLink\Link;

#[ErrorResource(
    uriVariables: ['status'],
    requirements: ['status' => '\d+'],
    uriTemplate: '/errors/{status}{._format}',
    openapi: false,
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
            outputFormats: [
                'html' => ['text/html'],
                'jsonapi' => ['application/vnd.api+json'],
                'jsonld' => ['application/ld+json'],
                'json' => ['application/problem+json', 'application/json'],
            ],
        ),
    ],
    outputFormats: ['jsonapi' => ['application/vnd.api+json'], 'jsonld' => ['application/ld+json'], 'json' => ['application/problem+json', 'application/json']],
    provider: 'api_platform.state.error_provider',
    graphQlOperations: [],
    description: 'A representation of common errors.',
)]
#[ApiProperty(property: 'previous', hydra: false, readable: false)]
#[ApiProperty(property: 'traceAsString', hydra: false, readable: false)]
#[ApiProperty(property: 'string', hydra: false, readable: false)]
class Error extends \Exception implements ProblemExceptionInterface, HttpExceptionInterface, ErrorResourceInterface
{
    private ?string $id = null;

    public function __construct(
        private string $title,
        private string $detail,
        #[ApiProperty(
            description: 'The HTTP status code applicable to this problem.',
            identifier: true,
            writable: false,
            initializable: false,
            schema: ['type' => 'number', 'examples' => [404], 'default' => 400]
        )] private int $status,
        ?array $originalTrace = null,
        private ?string $instance = null,
        private string $type = 'about:blank',
        private array $headers = [],
        ?\Throwable $previous = null,
        private ?array $meta = null,
        private ?array $source = null,
    ) {
        parent::__construct($title, $status, $previous);

        if (!$originalTrace) {
            return;
        }

        $this->originalTrace = [];
        foreach ($originalTrace as $i => $t) {
            unset($t['args']); // we don't want arguments in our JSON traces, especially with xdebug
            $this->originalTrace[$i] = $t;
        }
    }

    #[Groups(['jsonapi'])]
    public function getId(): string
    {
        return $this->id ?? ((string) $this->status);
    }

    #[Groups(['jsonapi'])]
    #[ApiProperty(schema: ['type' => 'object'])]
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    #[Groups(['jsonapi'])]
    #[ApiProperty(schema: [
        'type' => 'object',
        'properties' => [
            'pointer' => ['type' => 'string'],
            'parameter' => ['type' => 'string'],
            'header' => ['type' => 'string'],
        ],
    ])]
    public function getSource(): ?array
    {
        return $this->source;
    }

    #[SerializedName('trace')]
    #[Groups(['trace'])]
    #[ApiProperty(writable: false, initializable: false)]
    public ?array $originalTrace = null;

    #[Groups(['jsonld'])]
    #[ApiProperty(writable: false, initializable: false)]
    public function getDescription(): ?string
    {
        return $this->detail;
    }

    public static function createFromException(\Exception|\Throwable $exception, int $status): self
    {
        $headers = ($exception instanceof SymfonyHttpExceptionInterface || $exception instanceof HttpExceptionInterface) ? $exception->getHeaders() : [];

        return new self('An error occurred', $exception->getMessage(), $status, $exception->getTrace(), type: "/errors/$status", headers: $headers, previous: $exception->getPrevious());
    }

    #[Ignore]
    #[ApiProperty(readable: false)]
    public function getHeaders(): array
    {
        return $this->headers;
    }

    #[Ignore]
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    #[Groups(['jsonld', 'jsonproblem', 'jsonapi'])]
    #[ApiProperty(writable: false, initializable: false, description: 'A URI reference that identifies the problem type')]
    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    #[Groups(['jsonld', 'jsonproblem', 'jsonapi'])]
    #[ApiProperty(writable: false, initializable: false, description: 'A short, human-readable summary of the problem.')]
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title = null): void
    {
        $this->title = $title;
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
    #[ApiProperty(writable: false, initializable: false, description: 'A human-readable explanation specific to this occurrence of the problem.')]
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail = null): void
    {
        $this->detail = $detail;
    }

    #[Groups(['jsonld', 'jsonproblem', 'jsonapi'])]
    #[ApiProperty(writable: false, initializable: false, description: 'A URI reference that identifies the specific occurrence of the problem. It may or may not yield further information if dereferenced.')]
    public function getInstance(): ?string
    {
        return $this->instance;
    }

    public function setInstance(?string $instance = null): void
    {
        $this->instance = $instance;
    }

    public function setId(?string $id = null): void
    {
        $this->id = $id;
    }

    public function setMeta(?array $meta = null): void
    {
        $this->meta = $meta;
    }

    public function setSource(?array $source = null): void
    {
        $this->source = $source;
    }
}
