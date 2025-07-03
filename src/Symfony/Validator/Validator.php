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

namespace ApiPlatform\Symfony\Validator;

use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Validates an item using the Symfony validator component.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Validator implements ValidatorInterface
{
    public function __construct(private readonly SymfonyValidatorInterface $validator, private readonly ?ContainerInterface $container = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function validate(object $data, array $context = []): void
    {
        if (null !== $validationGroups = $context['groups'] ?? null) {
            if (
                $this->container
                && \is_string($validationGroups)
                && $this->container->has($validationGroups)
                && ($service = $this->container->get($validationGroups))
                && \is_callable($service)
            ) {
                $validationGroups = $service($data);
            } elseif (\is_callable($validationGroups)) {
                $validationGroups = $validationGroups($data);
            }

            if (!$validationGroups instanceof GroupSequence) {
                $validationGroups = (array) $validationGroups;
            }
        }

        $violations = $this->validator->validate($data, null, $validationGroups);
        if (0 !== \count($violations)) {
            throw new ValidationException($violations);
        }
    }
}
