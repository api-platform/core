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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @Annotation
 */
final class DummyCompoundRequirements extends Compound
{
    public function getConstraints(array $options): array
    {
        return [
            new Length(['min' => 1, 'max' => 32]),
            new Regex(['pattern' => '/^[a-z]$/']),
        ];
    }
}
