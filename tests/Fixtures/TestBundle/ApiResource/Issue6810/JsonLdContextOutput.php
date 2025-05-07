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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6810;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get('/json_ld_context_output', provider: [self::class, 'getData'], output: Output::class, normalizationContext: ['hydra_prefix' => false], openapi: false)]
class JsonLdContextOutput
{
    public function __construct(public string $id)
    {
    }

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): Output
    {
        return new Output();
    }
}
