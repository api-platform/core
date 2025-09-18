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

namespace ApiPlatform\Metadata;

/**
 * @internal
 *
 * @phpstan-require-extends Operation
 */
trait CascadeFromResource
{
    use WithResourceTrait;

    public function cascadeFromResource(ApiResource $apiResource, array $ignoredOptions = []): static
    {
        return $this->copyFrom($apiResource, $ignoredOptions);
    }
}
