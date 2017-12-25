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

use ApiPlatform\Core\Bridge\Symfony\Validator\Validator;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
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

    /**
     * @expectedException \ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException
     */
    public function testInvalid()
    {
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
}
