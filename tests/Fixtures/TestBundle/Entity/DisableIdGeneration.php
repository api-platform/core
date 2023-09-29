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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get('disable_id_generation_collection', provider: [DisableIdGeneration::class, 'provide'])]
class DisableIdGeneration
{
    #[ApiProperty(identifier: true)]
    public int $id;

    /**
     * @var array<DisableIdGenerationItem>
     */
    #[ApiProperty(genId: false)]
    public array $disableIdGenerationItems;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $a = new self();
        $a->disableIdGenerationItems = [new DisableIdGenerationItem('test'), new DisableIdGenerationItem('test2')];

        return $a;
    }
}

class DisableIdGenerationItem
{
    public function __construct(public string $title)
    {
    }
}
