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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Validator;

use ApiPlatform\Symfony\Validator\ValidationGroupsGeneratorInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;

final class DummyValidationGroupsGenerator implements ValidationGroupsGeneratorInterface
{
    public function __invoke($object): GroupSequence
    {
        return new GroupSequence(['b', 'a']);
    }
}
