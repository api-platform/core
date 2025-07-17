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

namespace ApiPlatform\Validator\Exception;

use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Util\CompositeIdentifierParser;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\WebLink\Link;

/**
 * Thrown when a validation error occurs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ErrorResource(
    uriTemplate: '/validation_errors/{id}',
    status: 422,
    uriVariables: ['id'],
    openapi: false,
    outputFormats: [
        'jsonapi' => ['application/vnd.api+json'],
        'jsonld' => ['application/ld+json'],
        'json' => ['application/problem+json', 'application/json'],
        'xml' => ['application/xml', 'text/xml'],
    ],
    provider: 'api_platform.validator.state.error_provider',
    shortName: 'ConstraintViolation',
    description: 'Unprocessable entity',
    operations: [
        new ErrorOperation(
            name: '_api_validation_errors_problem',
            outputFormats: [
                'json' => ['application/problem+json'],
            ],
            normalizationContext: [
                SchemaFactory::OPENAPI_DEFINITION_NAME => '',
                'groups' => ['json'],
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
                'skip_null_values' => true,
            ]
        ),
        new ErrorOperation(
            name: '_api_validation_errors_hydra',
            outputFormats: ['jsonld' => ['application/problem+json', 'application/ld+json']],
            links: [new Link(rel: 'http://www.w3.org/ns/json-ld#error', href: 'http://www.w3.org/ns/hydra/error')],
            normalizationContext: [
                SchemaFactory::OPENAPI_DEFINITION_NAME => '',
                'groups' => ['jsonld'],
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
                'skip_null_values' => true,
            ]
        ),
        new ErrorOperation(
            name: '_api_validation_errors_jsonapi',
            outputFormats: ['jsonapi' => ['application/vnd.api+json']],
            normalizationContext: [
                SchemaFactory::OPENAPI_DEFINITION_NAME => '',
                'disable_json_schema_serializer_groups' => false,
                'groups' => ['jsonapi'],
                'skip_null_values' => true,
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
            ]
        ),
        new ErrorOperation(
            name: '_api_validation_errors_xml',
            outputFormats: [
                'xml' => ['application/xml', 'text/xml'],
            ],
            normalizationContext: [
                'groups' => ['json'],
                'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
                'skip_null_values' => true,
            ]
        ),
    ],
    graphQlOperations: []
)]
#[ApiProperty(property: 'traceAsString', hydra: false)]
#[ApiProperty(property: 'string', hydra: false)]
class ValidationException extends RuntimeException implements ConstraintViolationListAwareExceptionInterface, \Stringable, ProblemExceptionInterface, HttpExceptionInterface, SymfonyHttpExceptionInterface
{
    private int $status = 422;
    protected ?string $errorTitle = null;
    private array|ConstraintViolationListInterface $constraintViolationList = [];

    public function __construct(string|ConstraintViolationListInterface $message = new ConstraintViolationList(), string|int|null $code = null, int|\Throwable|null $previous = null, \Throwable|string|null $errorTitle = null)
    {
        $this->errorTitle = $errorTitle;

        if ($message instanceof ConstraintViolationListInterface) {
            $this->constraintViolationList = $message;
            parent::__construct($this->__toString(), $code ?? 0, $previous);

            return;
        }

        trigger_deprecation('api_platform/core', '5.0', \sprintf('The "%s" exception will have a "%s" first argument in 5.x.', self::class, ConstraintViolationListInterface::class));
        parent::__construct($message ?: $this->__toString(), $code ?? 0, $previous);
    }

    public function getId(): string
    {
        $ids = [];
        foreach ($this->getConstraintViolationList() as $violation) {
            $ids[] = $violation->getCode();
        }

        $id = 1 < \count($ids) ? CompositeIdentifierParser::stringify(identifiers: $ids) : ($ids[0] ?? null);

        if (!$id) {
            return spl_object_hash($this);
        }

        return $id;
    }

    #[Groups(['jsonld'])]
    #[ApiProperty(writable: false, initializable: false)]
    public function getDescription(): string
    {
        return $this->detail;
    }

    #[Groups(['jsonld', 'json', 'jsonapi'])]
    #[ApiProperty(writable: false, initializable: false)]
    public function getType(): string
    {
        return '/validation_errors/'.$this->getId();
    }

    #[Groups(['jsonld', 'json', 'jsonapi'])]
    #[ApiProperty(writable: false, initializable: false)]
    public function getTitle(): ?string
    {
        return $this->errorTitle ?? 'An error occurred';
    }

    #[Groups(['jsonld', 'json', 'jsonapi'])]
    #[ApiProperty(writable: false, initializable: false)]
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
    #[ApiProperty(writable: false, initializable: false)]
    public function getInstance(): ?string
    {
        return null;
    }

    #[SerializedName('violations')]
    #[Groups(['json', 'jsonld'])]
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
    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
    }

    public function __toString(): string
    {
        $message = '';
        foreach ($this->getConstraintViolationList() as $violation) {
            if ('' !== $message) {
                $message .= "\n";
            }
            if ($propertyPath = $violation->getPropertyPath()) {
                $message .= "$propertyPath: ";
            }

            $message .= $violation->getMessage();
        }

        return $message;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
