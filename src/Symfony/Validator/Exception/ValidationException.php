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

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Util\CompositeIdentifierParser;
use ApiPlatform\Validator\Exception\ValidationException as BaseValidationException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Thrown when a validation error occurs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ErrorResource(uriTemplate: '/validation_errors/{id}', provider: 'api_platform.state_provider.default_error',
    status: 422,
    uriVariables: ['id'],
    shortName: 'ConstraintViolationList',
    operations: [
        new Get(name: '_api_validation_errors_hydra', outputFormats: ['jsonld' => ['application/ld+json']], normalizationContext: ['groups' => 'jsonld', 'skip_null_values' => true]),
        new Get(name: '_api_validation_errors_problem', outputFormats: ['jsonproblem' => ['application/problem+json']], normalizationContext: ['groups' => 'json', 'skip_null_values' => true]),
        new Get(name: '_api_validation_errors_jsonapi', outputFormats: ['jsonapi' => ['application/vnd.api+json']], normalizationContext: ['groups' => 'jsonapi', 'skip_null_values' => true], provider: 'api_platform.json_api.state_provider.default_error'),
    ]
)]
final class ValidationException extends BaseValidationException implements ConstraintViolationListAwareExceptionInterface, \Stringable, ProblemExceptionInterface
{
    public function __construct(private readonly ConstraintViolationListInterface $constraintViolationList, string $message = '', int $code = 0, \Throwable $previous = null, string $errorTitle = null)
    {
        parent::__construct($message ?: $this->__toString(), $code, $previous, $errorTitle);
    }

    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
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

    #[SerializedName('hydra:title')]
    #[Groups(['jsonld', 'legacy_jsonld'])]
    public function getHydraTitle(): string
    {
        return $this->errorTitle ?? 'An error occurred';
    }

    #[SerializedName('hydra:description')]
    #[Groups(['jsonld', 'legacy_jsonld'])]
    public function getHydraDescription(): string
    {
        return $this->__toString();
    }

    #[Groups(['jsonld', 'json', 'legacy_jsonproblem'])]
    public function getType(): string
    {
        return '/validation_errors/'.$this->getId();
    }

    #[Groups(['jsonld', 'json', 'legacy_jsonproblem'])]
    public function getTitle(): ?string
    {
        return $this->errorTitle ?? 'An error occurred';
    }

    #[Groups(['jsonld', 'json', 'legacy_jsonproblem'])]
    public function getDetail(): ?string
    {
        return $this->__toString();
    }

    #[Groups(['jsonld', 'json', 'legacy_jsonproblem'])]
    public function getStatus(): ?int
    {
        return 422;
    }

    #[Groups(['jsonld', 'json'])]
    public function getInstance(): ?string
    {
        return null;
    }

    #[SerializedName('violations')]
    #[Groups(['json', 'jsonld', 'legacy_jsonld', 'legacy_jsonproblem'])]
    public function getViolations(): iterable
    {
        foreach ($this->getConstraintViolationList() as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $violationData = [
                'propertyPath' => $propertyPath,
                'message' => $violation->getMessage(),
                'code' => $violation->getCode(),
            ];

            if ($hint = $violation->getParameters()['hint'] ?? false) {
                $violationData['hint'] = $hint;
            }

            yield $violationData;
        }
    }
}
