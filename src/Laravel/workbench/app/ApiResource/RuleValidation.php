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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    uriTemplate: '/issue6745/rule_validations',
    operations: [new Post()],
    rules: ['prop' => 'required', 'max' => 'lt:2']
)]
class RuleValidation
{
    public function __construct(public ?int $prop = null, public ?int $max = null)
    {
    }
}
