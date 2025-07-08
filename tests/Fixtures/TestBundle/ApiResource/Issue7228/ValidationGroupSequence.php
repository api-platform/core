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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7228;

use ApiPlatform\Metadata\Post;
use Symfony\Component\Validator\Constraints\GroupSequence;

#[Post(uriTemplate: 'issue7228', validationContext: ['groups' => new GroupSequence(['P1', 'P2'])])]
class ValidationGroupSequence
{
    public function __construct(
        public string $id = '1',
    ) {
    }
}
