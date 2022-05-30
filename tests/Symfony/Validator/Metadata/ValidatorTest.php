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

namespace ApiPlatform\Tests\Symfony\Validator;

use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Symfony\Validator\ValidationGroupsGeneratorInterface;
use ApiPlatform\Symfony\Validator\Validator;
use ApiPlatform\Tests\Fixtures\DummyEntity;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidatorTest extends TestCase
{
    use ProphecyTrait;

    public function testValid()
    {
        $data = new DummyEntity();

        $constraintViolationListProphecy = $this->prophesize(ConstraintViolationListInterface::class);
        $constraintViolationListProphecy->count()->willReturn(0);

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
        $constraintViolationListProphecy->count()->willReturn(0);

        $symfonyValidatorProphecy = $this->prophesize(SymfonyValidatorInterface::class);
        $symfonyValidatorProphecy->validate($data, null, $expectedValidationGroups)->willReturn($constraintViolationListProphecy->reveal())->shouldBeCalled();
        $symfonyValidator = $symfonyValidatorProphecy->reveal();

        $validator = new Validator($symfonyValidator);
        $validator->validate(new DummyEntity(), ['groups' => function ($data) use ($expectedValidationGroups): array {
            return $data instanceof DummyEntity ? $expectedValidationGroups : [];
        }]);
    }

    public function testValidateGetGroupsFromService(): void
    {
        $data = new DummyEntity();

        $constraintViolationListProphecy = $this->prophesize(ConstraintViolationListInterface::class);
        $constraintViolationListProphecy->count()->willReturn(0);

        $symfonyValidatorProphecy = $this->prophesize(SymfonyValidatorInterface::class);
        $symfonyValidatorProphecy->validate($data, null, ['a', 'b', 'c'])->willReturn($constraintViolationListProphecy)->shouldBeCalled();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('groups_builder')->willReturn(true);
        $containerProphecy->get('groups_builder')->willReturn(new class() implements ValidationGroupsGeneratorInterface {
            public function __invoke($data): array
            {
                return $data instanceof DummyEntity ? ['a', 'b', 'c'] : [];
            }
        });

        $validator = new Validator($symfonyValidatorProphecy->reveal(), $containerProphecy->reveal());
        $validator->validate(new DummyEntity(), ['groups' => 'groups_builder']);
    }

    public function testValidatorWithScalarGroup()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['foo'];

        $constraintViolationListProphecy = $this->prophesize(ConstraintViolationListInterface::class);
        $constraintViolationListProphecy->count()->willReturn(0);

        $symfonyValidatorProphecy = $this->prophesize(SymfonyValidatorInterface::class);
        $symfonyValidatorProphecy->validate($data, null, $expectedValidationGroups)->willreturn($constraintViolationListProphecy->reveal())->shouldBeCalled();
        $symfonyValidator = $symfonyValidatorProphecy->reveal();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('foo')->willReturn(false)->shouldBeCalled();

        $validator = new Validator($symfonyValidator, $containerProphecy->reveal());
        $validator->validate(new DummyEntity(), ['groups' => 'foo']);
    }
}
