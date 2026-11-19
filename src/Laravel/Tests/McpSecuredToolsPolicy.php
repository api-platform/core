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

namespace ApiPlatform\Laravel\Tests;

use Illuminate\Foundation\Auth\User;
use Workbench\App\ApiResource\McpSecuredTools;

class McpSecuredToolsPolicy
{
    public function viewAny(?User $user): bool
    {
        return false;
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, McpSecuredTools $resource): bool
    {
        return true;
    }
}
