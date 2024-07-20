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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6446;

use ApiPlatform\Metadata\Post;
use Symfony\Component\Validator\Constraints\NotNull;

#[Post(uriTemplate: 'issue_6446', securityPostValidation: 'is_granted(\'ROLE_ADMIN\')')]
class SecurityPostValidation
{
    #[NotNull]
    public string $title;
}
