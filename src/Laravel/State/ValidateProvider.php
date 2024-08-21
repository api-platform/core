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

use ApiPlatform\Laravel\ApiResource\ValidationError;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @implements ProviderInterface<object>
 */
final class ValidateProvider implements ProviderInterface
{
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

        if (!$operation->canValidate() || $operation instanceof Error) {
            return $body;
        }

        $rules = $operation->getRules();
        if (\is_callable($rules)) {
            $rules = $rules();
        }

        if (\is_string($rules) && is_a($rules, FormRequest::class, true)) {
            try {
                $this->app->make($rules);
                // } catch (AuthorizationException $e) { // TODO: we may want to catch this to transform to an error
            } catch (ValidationException $e) { // @phpstan-ignore-line make->($rules) may throw this
                throw $this->getValidationError($e);
            }

            return $body;
        }

        if (!\is_array($rules)) {
            return $body;
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            throw $this->getValidationError(new ValidationException($validator));
        }

        return $body;
    }

    private function getValidationError(ValidationException $e): ValidationError
    {
        $violations = [];
        foreach ($e->validator->errors()->messages() as $prop => $message) {
            $violations[] = ['propertyPath' => $prop, 'message' => implode(\PHP_EOL, $message)];
        }

        return new ValidationError($e->getMessage(), spl_object_hash($e), $e, $violations);
    }
}
