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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Metadata\Get;

if (\PHP_VERSION_ID > 80000) {
    #[Get]
    #[Get('/alternate/{id}', uriVariables: ['id' => ['from_class' => AlternateResource::class, 'identifiers' => ['id']]])]
    final class AlternateResource
    {
        #[ApiProperty(identifier: true)]
        public string $id;

        public function __construct(string $id)
        {
            $this->id = $id;
        }

        public function getId()
        {
            return $this->id;
        }
    }
}
