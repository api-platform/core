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

namespace ApiPlatform\Symfony\Tests\Validator\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as ModelParameter;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Validator\State\ParameterValidatorProvider;
use ApiPlatform\Validator\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\Validator\Constraint\NotBlank;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ParameterValidatorProviderTest extends TestCase
{
    public function testProvideWithoutRequest(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn(new \stdClass());

        $provider = new ParameterValidatorProvider($validator, $decorated);
        $result = $provider->provide(new Get(), [], []);

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testProvideWithValidationDisabled(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn(new \stdClass());

        $operation = (new Get())->withQueryParameterValidationEnabled(false);
        $request = new Request();
        $request->attributes->set('_api_operation', $operation);

        $provider = new ParameterValidatorProvider($validator, $decorated);
        $result = $provider->provide($operation, [], ['request' => $request]);

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testProvideWithNoConstraints(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn(new \stdClass());

        $operation = new Get(parameters: new Parameters([
            'foo' => new QueryParameter(key: 'foo'),
        ]));
        $request = new Request();
        $request->attributes->set('_api_operation', $operation);

        $provider = new ParameterValidatorProvider($validator, $decorated);
        $result = $provider->provide($operation, [], ['request' => $request]);

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testProvideWithValidParameters(): void
    {
        $constraint = new NotBlank();
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->willReturn(new ConstraintViolationList());
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn(new \stdClass());

        $operation = new Get(parameters: new Parameters([
            'foo' => (new QueryParameter(key: 'foo'))->withConstraints([$constraint])->setValue('bar'),
        ]));
        $request = new Request();
        $request->attributes->set('_api_operation', $operation);

        $provider = new ParameterValidatorProvider($validator, $decorated);
        $result = $provider->provide($operation, [], ['request' => $request]);

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testProvideWithInvalidParameters(): void
    {
        $this->expectException(ValidationException::class);

        $constraint = new NotBlank();
        $violationList = new ConstraintViolationList();
        $violationList->add($this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->willReturn($violationList);
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->never())->method('provide');

        $operation = new Get(parameters: new Parameters([
            'foo' => (new QueryParameter(key: 'foo'))->withConstraints([$constraint])->setValue(new ParameterNotFound()),
        ]));
        $request = new Request();
        $request->attributes->set('_api_operation', $operation);

        $provider = new ParameterValidatorProvider($validator, $decorated);
        $provider->provide($operation, [], ['request' => $request]);
    }

    public function testProvideWithUriVariables(): void
    {
        $constraint = new NotBlank();
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->willReturn(new ConstraintViolationList());
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn(new \stdClass());

        $operation = new Get(uriVariables: [
            'id' => (new Link())->withConstraints([$constraint])->setValue('1'),
        ]);
        $request = new Request();
        $request->attributes->set('_api_operation', $operation);

        $provider = new ParameterValidatorProvider($validator, $decorated);
        $result = $provider->provide($operation, ['id' => 1], ['request' => $request]);

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testGetPropertyWithDeepObject(): void
    {
        $constraint = new NotBlank();
        $violationList = new ConstraintViolationList();
        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn('[bar]');
        $violationList->add($violation);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->willReturn($violationList);
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->never())->method('provide');

        $parameter = (new QueryParameter(key: 'foo'))->withConstraints([$constraint])->setValue(new ParameterNotFound());
        $parameter = $parameter->withOpenApi(new ModelParameter(name: 'foo', in: 'query', style: 'deepObject'));

        $operation = new Get(parameters: new Parameters([
            'foo' => $parameter,
        ]));
        $request = new Request();
        $request->attributes->set('_api_operation', $operation);

        $provider = new ParameterValidatorProvider($validator, $decorated);
        try {
            $provider->provide($operation, [], ['request' => $request]);
        } catch (ValidationException $e) {
            $this->assertEquals('foo[bar]', $e->getConstraintViolationList()->get(0)->getPropertyPath());
        }
    }
}
