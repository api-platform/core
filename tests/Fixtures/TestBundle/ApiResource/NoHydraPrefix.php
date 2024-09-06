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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\GetCollection;

#[GetCollection(normalizationContext: ['hydra_prefix' => false], provider: [self::class, 'provide'])]
final class NoHydraPrefix
{
    public function __construct(public string $id, public string $title)
    {
    }

    /**
     * @return self[]
     */
    public static function provide(): array
    {
        return [
            new self('1', 'test'),
            new self('2', 'test'),
        ];
    }
}
