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

namespace ApiPlatform\Symfony\Validator\Exception;

use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Util\CompositeIdentifierParser;
use ApiPlatform\Validator\Exception\ValidationException as BaseValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
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
    openapi: false,
    uriVariables: ['id'],
    provider: 'api_platform.validator.state.error_provider',
    shortName: 'ConstraintViolationList',
    operations: [
        new ErrorOperation(
            name: '_api_validation_errors_problem',
            outputFormats: ['json' => ['application/problem+json']],
            normalizationContext: ['groups' => ['json'],
                'skip_null_values' => true,
                'rfc_7807_compliant_errors' => true,
            ]),
        new ErrorOperation(
            name: '_api_validation_errors_hydra',
            outputFormats: ['jsonld' => ['application/problem+json']],
            links: [new Link(rel: ContextBuilderInterface::JSONLD_NS.'error', href: 'http://www.w3.org/ns/hydra/error')],
            normalizationContext: [
                'groups' => ['jsonld'],
                'skip_null_values' => true,
                'rfc_7807_compliant_errors' => true,
            ]
        ),
        new ErrorOperation(
            name: '_api_validation_errors_jsonapi',
            outputFormats: ['jsonapi' => ['application/vnd.api+json']],
            normalizationContext: ['groups' => ['jsonapi'], 'skip_null_values' => true, 'rfc_7807_compliant_errors' => true]
        ),
    ],
    graphQlOperations: []
)]
final class ValidationException extends BaseValidationException implements ConstraintViolationListAwareExceptionInterface, \Stringable, ProblemExceptionInterface, HttpExceptionInterface
{
    private int $status = 422;

    public function __construct(private readonly ConstraintViolationListInterface $constraintViolationList, string $message = '', int $code = 0, ?\Throwable $previous = null, ?string $errorTitle = null)
    {
        parent::__construct($message ?: $this->__toString(), $code, $previous, $errorTitle);
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

    #[SerializedName('hydra:title')]
    #[Groups(['jsonld'])]
    public function getHydraTitle(): string
    {
        return $this->errorTitle ?? 'An error occurred';
    }

    #[Groups(['jsonld'])]
    #[SerializedName('hydra:description')]
    public function getHydraDescription(): string
    {
        return $this->detail;
    }

    #[Groups(['jsonld', 'json'])]
    public function getType(): string
    {
        return '/validation_errors/'.$this->getId();
    }

    #[Groups(['jsonld', 'json'])]
    public function getTitle(): ?string
    {
        return $this->errorTitle ?? 'An error occurred';
    }

    #[Groups(['jsonld', 'json'])]
    private string $detail;

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(string $detail): void
    {
        $this->detail = $detail;
    }

    #[Groups(['jsonld', 'json'])]
    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    #[Groups(['jsonld', 'json'])]
    public function getInstance(): ?string
    {
        return null;
    }

    #[SerializedName('violations')]
    #[Groups(['json', 'jsonld'])]
    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
    }

    public function __toString(): string
    {
        $message = '';
        foreach ($this->constraintViolationList as $violation) {
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
