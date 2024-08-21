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

use Illuminate\Http\Request;
use Workbench\App\Models\User;

Route::post('/tokens/create', function (Request $request) {
    $user = User::first();
    $token = $user->createToken('foo');

    return ['token' => $token->plainTextToken];
});
