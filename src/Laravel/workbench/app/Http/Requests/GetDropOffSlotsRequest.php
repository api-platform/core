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

namespace Workbench\App\Http\Requests;

use ApiPlatform\Laravel\State\ValidationErrorTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GetDropOffSlotsRequest extends FormRequest
{
    use ValidationErrorTrait;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pickupDate' => $this->query('pickupDate'),
            'pickupSlotId' => $this->query('pickupSlotId'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @docs/guides/return-the-iri-of-your-resources-relations.php array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pickupDate' => 'required|date|after_or_equal:today',
            'pickupSlotId' => 'required|string',
        ];
    }

    // to match api plaform validation response
    protected function failedValidation(Validator $validator): void
    {
        $violations = collect($validator->errors())
            ->map(fn ($m, $f) => ['propertyPath' => $f, 'message' => $m[0]]) // ** @phpstan-ignore-line */
            ->values()->all();

        throw new \ApiPlatform\Laravel\ApiResource\ValidationError($violations[0]['message'] ?? 'Validation failed.', hash('xxh3', implode(',', array_column($violations, 'propertyPath'))), new \Illuminate\Validation\ValidationException($validator), $violations);
    }
}
