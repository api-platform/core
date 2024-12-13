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

namespace ApiPlatform\Laravel\State;

use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @implements ProviderInterface<object>
 */
final class ValidateProvider implements ProviderInterface
{
    use ValidationErrorTrait;

    /**
     * @param ProviderInterface<object> $inner
     */
    public function __construct(
        private readonly ProviderInterface $inner,
        private readonly Application $app,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $context['request'];
        $body = $this->inner->provide($operation, $uriVariables, $context);

        if ($operation instanceof Error) {
            return $body;
        }

        $rules = $operation->getRules();
        if (\is_callable($rules)) {
            $rules = $rules();
        }

        if (\is_string($rules) && is_a($rules, FormRequest::class, true)) {
            try {
                // this also throws an AuthorizationException
                $this->app->make($rules);
            } catch (ValidationException $e) { // @phpstan-ignore-line make->($rules) may throw this
                if (!$operation->canValidate()) {
                    return $body;
                }

                throw $this->getValidationError($e->validator, $e);
            }

            return $body;
        }

        if (!$operation->canValidate()) {
            return $body;
        }

        if (!\is_array($rules)) {
            return $body;
        }

        // In Symfony, validation is done on the Resource object (here $body) using Deserialization before Validation
        // Here, we did not deserialize yet, we validate on the raw body before.
        $validationBody = $request->request->all();
        if ('jsonapi' === $request->getRequestFormat()) {
            $validationBody = $validationBody['data']['attributes'];
        }

        $validator = Validator::make($validationBody, $rules);
        if ($validator->fails()) {
            throw $this->getValidationError($validator, new ValidationException($validator));
        }

        return $body;
    }
}
