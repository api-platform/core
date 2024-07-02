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

namespace ApiPlatform\Tests;

use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyKernelTestCase;

abstract class KernelTestCase extends SymfonyKernelTestCase
{
    #[BeforeClass]
    public static function before(): void
    {
        static::bootKernel();
    }
}
