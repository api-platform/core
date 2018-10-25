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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Validator;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Bridge\Symfony\Validator\Validator;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidatorTest extends TestCase
{
    public function testValid()
    {
        $data = new DummyEntity();

        $constraintViolationListProphecy = $this->prophesize(ConstraintViolationListInterface::class);
        $symfonyValidatorProphecy = $this->prophesize(SymfonyValidatorInterface::class);
        $symfonyValidatorProphecy->validate($data, null, null)->willReturn($constraintViolationListProphecy->reveal())->shouldBeCalled();
        $symfonyValidator = $symfonyValidatorProphecy->reveal();

        $validator = new Validator($symfonyValidator);
        $validator->validate(new DummyEntity());
    }

    public function testInvalid()
    {
        $this->expectException(ValidationException::class);

        $data = new DummyEntity();

        $constraintViolationListProphecy = $this->prophesize(ConstraintViolationListInterface::class);
        $constraintViolationListProphecy->rewind()->shouldBeCalled();
        $constraintViolationListProphecy->valid()->shouldBeCalled();
        $constraintViolationListProphecy->count()->willReturn(2)->shouldBeCalled();

        $symfonyValidatorProphecy = $this->prophesize(SymfonyValidatorInterface::class);
        $symfonyValidatorProphecy->validate($data, null, null)->willReturn($constraintViolationListProphecy->reveal())->shouldBeCalled();
        $symfonyValidator = $symfonyValidatorProphecy->reveal();

        $validator = new Validator($symfonyValidator);
        $validator->validate(new DummyEntity());
    }

    public function testGetGroupsFromCallable()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $constraintViolationListProphecy = $this->prophesize(ConstraintViolationListInterface::class);
        $symfonyValidatorProphecy = $this->prophesize(SymfonyValidatorInterface::class);
        $symfonyValidatorProphecy->validate($data, null, $expectedValidationGroups)->willReturn($constraintViolationListProphecy->reveal())->shouldBeCalled();
        $symfonyValidator = $symfonyValidatorProphecy->reveal();

        $validator = new Validator($symfonyValidator);
        $validator->validate(new DummyEntity(), ['groups' => function ($data) use ($expectedValidationGroups): array {
            return $data instanceof DummyEntity ? $expectedValidationGroups : [];
        }]);
    }

    public function testGetGroupsFromService()
    {
        $data = new DummyEntity();

        $constraintViolationListProphecy = $this->prophesize(ConstraintViolationListInterface::class);
        $symfonyValidatorProphecy = $this->prophesize(SymfonyValidatorInterface::class);
        $symfonyValidatorProphecy->validate($data, null, ['a', 'b', 'c'])->willReturn($constraintViolationListProphecy->reveal())->shouldBeCalled();
        $symfonyValidator = $symfonyValidatorProphecy->reveal();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('groups_builder')->willReturn(true)->shouldBeCalled();
        $containerProphecy->get('groups_builder')->willReturn(new class() {
            public function __invoke($data): array
            {
                return $data instanceof DummyEntity ? ['a', 'b', 'c'] : [];
            }
        }
        )->shouldBeCalled();

        $validator = new Validator($symfonyValidator, $containerProphecy->reveal());
        $validator->validate(new DummyEntity(), ['groups' => 'groups_builder']);
    }

    public function testValidatorWithScalarGroup()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['foo'];

        $constraintViolationListProphecy = $this->prophesize(ConstraintViolationListInterface::class);
        $symfonyValidatorProphecy = $this->prophesize(SymfonyValidatorInterface::class);
        $symfonyValidatorProphecy->validate($data, null, $expectedValidationGroups)->willreturn($constraintViolationListProphecy->reveal())->shouldBeCalled();
        $symfonyValidator = $symfonyValidatorProphecy->reveal();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('foo')->willReturn(false)->shouldBeCalled();

        $validator = new Validator($symfonyValidator, $containerProphecy->reveal());
        $validator->validate(new DummyEntity(), ['groups' => 'foo']);
    }

    public function testOnlyValidatesSubmittedPropertiesWithIncompletePutRequest()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['foo'];

        $constraintViolationListProphecy = $this->prophesize(ConstraintViolationListInterface::class);

        $contextualValidatorProphecy = $this->prophesize(ContextualValidatorInterface::class);
        $contextualValidatorProphecy->getViolations()->willReturn($constraintViolationListProphecy->reveal())->shouldBeCalled();
        $contextualValidatorProphecy->validateProperty($data, 'propertyName', $expectedValidationGroups)->willReturn($constraintViolationListProphecy->reveal())->shouldBeCalled();

        $symfonyValidatorProphecy = $this->prophesize(SymfonyValidatorInterface::class);
        $symfonyValidatorProphecy->startContext()->willReturn($contextualValidatorProphecy->reveal())->shouldBeCalled();
        $symfonyValidator = $symfonyValidatorProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn('put')->shouldBeCalled();
        $requestProphecy->getContent()->willReturn('{}')->shouldBeCalled();
        $requestProphecy->getRequestFormat()->willReturn('json')->shouldBeCalled();

        $requestStackProphecy = $this->prophesize(RequestStack::class);
        $requestStackProphecy->getCurrentRequest()->willReturn($requestProphecy->reveal())->shouldBeCalled();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('request_stack')->willReturn($requestStackProphecy->reveal())->shouldBeCalled();

        $decoderProphecy = $this->prophesize(DecoderInterface::class);
        $decoderProphecy->decode('{}', 'json')->willReturn(['propertyName' => 'propertyValue'])->shouldBeCalled();

        $validator = new Validator($symfonyValidator, $containerProphecy->reveal(), $decoderProphecy->reveal());
        $validator->validate(new DummyEntity(), ['groups' => $expectedValidationGroups]);
    }
}
