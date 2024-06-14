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

namespace ApiPlatform\Symfony\Validator\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates data on an HTTP or GraphQl operation.
 */
final class ValidateProvider implements ProviderInterface
{
    public function __construct(private readonly ?ProviderInterface $decorated, private readonly ValidatorInterface $validator, private readonly string $canValidateAccessor = 'canValidate')
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $body = $this->decorated?->provide($operation, $uriVariables, $context) ?? ($context['request'] ?? null)?->attributes->get('data');

        if ($body instanceof Response || !$body) {
            return $body;
        }

        if (method_exists($operation, $this->canValidateAccessor) && !($operation->{$this->canValidateAccessor}() ?? ('canValidate' === $this->canValidateAccessor))) {
            return $body;
        }

        $this->validator->validate($body, $operation->getValidationContext() ?? []);

        return $body;
    }
}
