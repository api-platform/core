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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;

#[Get(
    shortName: 'JsonLdDisableIdGenAnonymous',
    uriTemplate: '/jsonld_disable_id_gen_anonymous',
    provider: [self::class, 'provide'],
)]
class DisableIdGenAnonymous
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    /** @var array<DisableIdGenItem> */
    #[ApiProperty(genId: false)]
    public array $items;

    public static function provide(): self
    {
        $a = new self();
        $a->items = [new DisableIdGenItem('one'), new DisableIdGenItem('two')];

        return $a;
    }
}

class DisableIdGenItem
{
    public function __construct(public string $title)
    {
    }
}
