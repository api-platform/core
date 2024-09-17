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

namespace Workbench\App\Policies;

use Illuminate\Foundation\Auth\User;
use Workbench\App\Models\Vault;

class VaultPolicy
{
    public function update(User $user, Vault $vault): bool
    {
        return false;
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
