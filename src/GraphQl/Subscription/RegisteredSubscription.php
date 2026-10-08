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

namespace ApiPlatform\GraphQl\Subscription;

/**
 * A registration and its publication state at the time it was read.
 *
 * @internal
 */
final readonly class RegisteredSubscription
{
    /** @param array{hash: string|null, version: string}|null $fingerprint */
    public function __construct(public string $key, public string $id, public array $fields, public bool $collection, public ?array $fingerprint)
    {
    }
}
