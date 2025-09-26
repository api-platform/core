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

use ApiPlatform\Metadata\IriConverterInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Workbench\App\Models\Area;

class StoreSlotRequest extends FormRequest
{
    public static ?Area $receivedArea = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $iriConverter = App::get(IriConverterInterface::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'area' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($iriConverter): void {
                    if (!(self::$receivedArea = $iriConverter->getResourceFromIri($value))) {
                        $fail("The {$attribute} is invalid.");
                    }
                },
            ],
        ];
    }
}
