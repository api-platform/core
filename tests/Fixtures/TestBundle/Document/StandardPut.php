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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
#[ApiResource(
    operations: [new Get(), new Put(allowCreate: true)],
    extraProperties: [
        'standard_put' => true,
    ]
)]
#[ODM\Document]
class StandardPut
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public ?int $id = null;

    #[ODM\Field]
    public string $foo = '';

    #[ODM\Field]
    public string $bar = '';
}

