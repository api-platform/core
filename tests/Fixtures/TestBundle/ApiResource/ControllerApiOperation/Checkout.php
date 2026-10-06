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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Operation;

class Checkout
{
    #[ApiProperty(identifier: true)]
    public int $id = 0;
    public string $reference = '';
    public string $status = '';

    public static function create(int $id): ?self
    {
        if (!\in_array($id, [1, 2], true)) {
            return null;
        }

        $checkout = new self();
        $checkout->id = $id;
        $checkout->reference = 'ref-'.$id;
        $checkout->status = 'pending';

        return $checkout;
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        return self::create((int) $uriVariables['id']);
    }
}
