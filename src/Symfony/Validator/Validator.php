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
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Validates an item using the Symfony validator component.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Validator implements ValidatorInterface
{
    use ValidationGroupsExtractorTrait;

    public function __construct(private readonly SymfonyValidatorInterface $validator, ?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(object $data, array $context = []): void
    {
        $violations = $this->validator->validate($data, null, $this->getValidationGroups($context['groups'] ?? null, $data));
        if (0 !== \count($violations)) {
            throw new ValidationException($violations);
        }
    }
}
