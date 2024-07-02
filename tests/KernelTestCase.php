<?php

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
