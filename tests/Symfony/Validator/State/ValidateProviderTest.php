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

namespace ApiPlatform\Tests\Symfony\Validator\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Validator\State\ValidateProvider;
use ApiPlatform\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;

class ValidateProviderTest extends TestCase
{
    public function testValidate(): void
    {
        $obj = new \stdClass();
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $validationContext = ['test'];
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->with($obj, $validationContext);
        $provider = new ValidateProvider($decorated, $validator);
        $provider->provide(new Get(validationContext: $validationContext));
    }

    public function testNoValidate(): void
    {
        $obj = new \stdClass();
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');
        $provider = new ValidateProvider($decorated, $validator);
        $provider->provide(new Get(validate: false));
    }
}
