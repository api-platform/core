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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\WebLink\Link;

/**
 * Thrown when a validation error occurs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ErrorResource(
    uriTemplate: '/validation_errors/{id}',
    status: 422,
    openapi: false,
    outputFormats: ['jsonapi' => ['application/vnd.api+json'], 'jsonld' => ['application/ld+json'], 'json' => ['application/problem+json', 'application/json']],
    uriVariables: ['id'],
    shortName: 'ValidationError',
    operations: [
        new ErrorOperation(
            routeName: 'api_validation_errors',
            name: '_api_validation_errors_problem',
            outputFormats: ['json' => ['application/problem+json']],
            normalizationContext: [
                'groups' => ['json'],
                'skip_null_values' => true,
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
            ],
        ),
        new ErrorOperation(
            name: '_api_validation_errors_hydra',
            routeName: 'api_validation_errors',
            outputFormats: ['jsonld' => ['application/problem+json']],
            links: [new Link(rel: 'http://www.w3.org/ns/json-ld#error', href: 'http://www.w3.org/ns/hydra/error')],
            normalizationContext: [
                'groups' => ['jsonld'],
                'skip_null_values' => true,
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
            ],
        ),
        new ErrorOperation(
            name: '_api_validation_errors_jsonapi',
            routeName: 'api_validation_errors',
            outputFormats: ['jsonapi' => ['application/vnd.api+json']],
            normalizationContext: [
                'groups' => ['jsonapi'],
                'skip_null_values' => true,
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
            ],
        ),
    ],
    graphQlOperations: []
)]
class ValidationError extends RuntimeException implements \Stringable, ProblemExceptionInterface, HttpExceptionInterface, SymfonyHttpExceptionInterface
{
    private int $status = 422;
    private string $id;

    /**
     * @param array<int, array{propertyPath: string, message: string, code?: string}> $violations
     */
    public function __construct(string $message = '', mixed $code = null, ?\Throwable $previous = null, protected array $violations = [])
    {
        $this->id = (string) $code;
        $this->setDetail($message);
        parent::__construct($message ?: $this->__toString(), 422, $previous);
    }

    public function getId(): string
    {
        return $this->id;
    }

    #[SerializedName('description')]
    #[Groups(['jsonld', 'json'])]
    public function getDescription(): string
    {
        return $this->detail;
    }

    #[Groups(['jsonld', 'json', 'jsonapi'])]
    public function getType(): string
    {
        return '/validation_errors/'.$this->id;
    }

    #[Groups(['jsonld', 'json', 'jsonapi'])]
    public function getTitle(): ?string
    {
        return 'Validation Error';
    }

    #[Groups(['jsonld', 'json', 'jsonapi'])]
    private string $detail;

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(string $detail): void
    {
        $this->detail = $detail;
    }

    #[Groups(['jsonld', 'json', 'jsonapi'])]
    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    #[Groups(['jsonld', 'json', 'jsonapi'])]
    public function getInstance(): ?string
    {
        return null;
    }

    /**
     * @return array<int,array{propertyPath:string,message:string,code?:string}>
     */
    #[SerializedName('violations')]
    #[Groups(['json', 'jsonld', 'jsonapi'])]
    #[ApiProperty(
        jsonldContext: ['@type' => 'ConstraintViolationList'],
        schema: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'propertyPath' => ['type' => 'string', 'description' => 'The property path of the violation'],
                    'message' => ['type' => 'string', 'description' => 'The message associated with the violation'],
                ],
            ],
        ]
    )]
    public function getViolations(): array
    {
        return $this->violations;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];
    }
}
