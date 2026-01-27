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

namespace Workbench\App\ApiResource;

use ApiPlatform\Metadata\Get;
use Workbench\App\Http\Controllers\WithCustomControllerController;

#[Get(uriTemplate: '/with_custom_controller/{id}', controller: WithCustomControllerController::class)]
class WithCustomController
{
    public function __construct(public ?int $id = null, public ?string $name = null)
    {
    }
}
