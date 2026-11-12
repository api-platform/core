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

namespace ApiPlatform\Mcp\Tests\Fixtures;

use Mcp\Capability\RegistryInterface;

interface ListChangePublishingRegistryInterface extends RegistryInterface
{
    public function publishesListChanges(): bool;
}
