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

namespace ApiPlatform\Elasticsearch\State;

use ApiPlatform\State\OptionsInterface as AbstractOptionsInterface;

interface OptionsInterface extends AbstractOptionsInterface
{
    public function getIndex(): ?string;

    public function getType(): ?string;
}
