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
use Workbench\App\Models\OwnedNote;

class OwnedNotePolicy
{
    public function view(User $user, OwnedNote $note): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, OwnedNote $note): bool
    {
        return $user->getKey() === $note->user_id;
    }
}
