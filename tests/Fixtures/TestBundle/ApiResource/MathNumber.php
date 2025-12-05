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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

if (class_exists(\BcMath\Number::class)) {
    #[Get(
        provider: [self::class, 'provide'],
    )]
    #[Post]
    class MathNumber
    {
        #[ApiProperty(identifier: true)]
        public int $id;

        #[ApiProperty(property: 'value')]
        public ?\BcMath\Number $value;

        public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
        {
            $mathNumber = new self();
            $mathNumber->id = $uriVariables['id'];
            $mathNumber->value = new \BcMath\Number('300.55');

            return $mathNumber;
        }
    }
}
