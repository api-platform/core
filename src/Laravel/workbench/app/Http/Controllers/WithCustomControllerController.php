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

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WithCustomControllerController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');

        // Plain controller - no API Platform features
        // Just return whatever you want
        return new JsonResponse([
            'id' => $id,
            'name' => 'Custom Controller Response',
            'custom' => true,
        ]);
    }
}
