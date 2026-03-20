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
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates data in the processor chain (after ObjectMapper transformations).
 *
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 */
final class ValidateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed,mixed>|null $decorated
     */
    public function __construct(
        private readonly ?ProcessorInterface $decorated,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Response || !$data || false === ($operation->canWrite() ?? true)) {
            return $this->decorated ? $this->decorated->process($data, $operation, $uriVariables, $context) : $data;
        }

        if (false === ($operation->canValidate() ?? true)) {
            return $this->decorated ? $this->decorated->process($data, $operation, $uriVariables, $context) : $data;
        }

        // Only validate at the processor level when ObjectMapper is used (canMap() is true).
        // Without ObjectMapper, ValidateProvider already handles validation in the provider chain,
        // so running it again here would cause duplicate external API calls and DB queries.
        if (!$operation->canMap()) {
            return $this->decorated ? $this->decorated->process($data, $operation, $uriVariables, $context) : $data;
        }

        $this->validator->validate($data, $operation->getValidationContext() ?? []);

        return $this->decorated ? $this->decorated->process($data, $operation, $uriVariables, $context) : $data;
    }
}
