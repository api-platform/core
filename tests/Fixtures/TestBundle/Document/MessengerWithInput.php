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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\MessengerInput;

#[ApiResource(graphQlOperations: [new Mutation(name: 'create', input: MessengerInput::class, messenger: 'input')], messenger: 'input', input: MessengerInput::class)]
class MessengerWithInput
{
    /**
     * @ApiProperty(identifier=true)
     */
    public $id;
    /**
     * @var string
     */
    public $name;
}
