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
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

trait ValidationErrorTrait
{
    private ?NameConverterInterface $nameConverter = null;

    private function getValidationError(Validator $validator, ValidationException $e): ValidationError
    {
        $errors = $validator->errors();
        $violations = [];
        $id = hash('xxh3', implode(',', $errors->keys()));
        foreach ($errors->messages() as $prop => $message) {
            $violations[] = ['propertyPath' => $this->nameConverter ? $this->nameConverter->normalize($prop) : $prop, 'message' => implode(\PHP_EOL, $message)];
        }

        return new ValidationError($e->getMessage(), $id, $e, $violations);
    }
}
