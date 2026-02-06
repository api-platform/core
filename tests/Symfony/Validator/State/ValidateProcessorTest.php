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

use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\Validator\State\ValidateProcessor;
use ApiPlatform\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ValidateProcessorTest extends TestCase
{
    public function testValidate(): void
    {
        $obj = new \stdClass();
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->with($obj)->willReturn($obj);
        $validationContext = ['test'];
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->with($obj, $validationContext);
        $processor = new ValidateProcessor($decorated, $validator);
        $processor->process($obj, new Post(validationContext: $validationContext));
    }

    public function testNoValidate(): void
    {
        $obj = new \stdClass();
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->with($obj)->willReturn($obj);
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');
        $processor = new ValidateProcessor($decorated, $validator);
        $processor->process($obj, new Post(validate: false));
    }

    public function testSkipsResponseObjects(): void
    {
        $response = new Response();
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->with($response)->willReturn($response);
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');
        $processor = new ValidateProcessor($decorated, $validator);
        $processor->process($response, new Post());
    }

    public function testSkipsNullData(): void
    {
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->with(null)->willReturn(null);
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');
        $processor = new ValidateProcessor($decorated, $validator);
        $processor->process(null, new Post());
    }

    public function testPassesValidationContext(): void
    {
        $obj = new \stdClass();
        $validationContext = ['groups' => ['create', 'strict']];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->with($obj)->willReturn($obj);
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->with($obj, $validationContext);
        $processor = new ValidateProcessor($decorated, $validator);
        $processor->process($obj, new Post(validationContext: $validationContext));
    }

    public function testDecoratorChainContinues(): void
    {
        $obj = new \stdClass();
        $result = new \stdClass();
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->with($obj)->willReturn($result);
        $validator = $this->createMock(ValidatorInterface::class);
        $processor = new ValidateProcessor($decorated, $validator);
        $this->assertSame($result, $processor->process($obj, new Post()));
    }

    public function testDeleteReturnsNull(): void
    {
        $obj = new \stdClass();
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->with($obj)->willReturn(null);
        $validator = $this->createMock(ValidatorInterface::class);
        $processor = new ValidateProcessor($decorated, $validator);
        $result = $processor->process($obj, new Post());
        $this->assertNull($result);
    }

    public function testSkipsWhenCanWriteIsFalse(): void
    {
        $obj = new \stdClass();
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->with($obj)->willReturn($obj);
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');
        $processor = new ValidateProcessor($decorated, $validator);
        // Delete operations typically have write: false
        $processor->process($obj, new Post(write: false));
    }
}
