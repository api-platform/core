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
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
        // TODO: trigger deprecation in API Platform 4.2 when this is not defined
        private readonly ?NormalizerInterface $normalizer = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        $this->nameConverter = $nameConverter;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
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

        $validationBody = $this->getBodyForValidation($body);

        $validator = Validator::make($validationBody, $rules);
        if ($validator->fails()) {
            throw $this->getValidationError($validator, new ValidationException($validator));
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    private function getBodyForValidation(mixed $body): array
    {
        if (!$body) {
            return [];
        }

        if ($body instanceof Model) {
            return $body->toArray();
        }

        if ($this->normalizer) {
            if (!\is_array($v = $this->normalizer->normalize($body))) {
                throw new RuntimeException('An array is expected.');
            }

            return $v;
        }

        // hopefully this path never gets used, its there for BC-layer only
        // TODO: deprecation in API Platform 4.2
        // TODO: remove in 5.0
        if ($s = json_encode($body)) {
            return json_decode($s, true);
        }

        throw new RuntimeException('Could not transform the denormalized body in an array for validation');
    }
}
