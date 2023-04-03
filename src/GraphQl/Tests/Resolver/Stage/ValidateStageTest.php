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

namespace ApiPlatform\GraphQl\Tests\Resolver\Stage;

use ApiPlatform\GraphQl\Resolver\Stage\ValidateStage;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ValidateStageTest extends TestCase
{
    use ProphecyTrait;

    private ValidateStage $validateStage;
    private ObjectProphecy $validatorProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->validatorProphecy = $this->prophesize(ValidatorInterface::class);

        $this->validateStage = new ValidateStage(
            $this->validatorProphecy->reveal()
        );
    }

    public function testApplyDisabled(): void
    {
        $resourceClass = 'myResource';
        /** @var Operation $operation */
        $operation = (new Query())->withValidate(false)->withName('item_query');

        $this->validatorProphecy->validate(Argument::cetera())->shouldNotBeCalled();

        ($this->validateStage)(new \stdClass(), $resourceClass, $operation, []);
    }

    public function testApply(): void
    {
        $resourceClass = 'myResource';
        $validationGroups = ['group'];
        /** @var Operation $operation */
        $operation = (new Query())->withName('item_query')->withValidationContext(['groups' => $validationGroups]);

        $object = new \stdClass();
        $this->validatorProphecy->validate($object, ['groups' => $validationGroups])->shouldBeCalled();

        ($this->validateStage)($object, $resourceClass, $operation, []);
    }

    public function testApplyNotValidated(): void
    {
        $resourceClass = 'myResource';
        $validationGroups = ['group'];
        /** @var Operation $operation */
        $operation = (new Query())->withValidationContext(['groups' => $validationGroups])->withName('item_query');
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $context = ['info' => $info];

        $object = new \stdClass();
        $this->validatorProphecy->validate($object, ['groups' => $validationGroups])->shouldBeCalled()->willThrow(new ValidationException());

        $this->expectException(ValidationException::class);

        ($this->validateStage)($object, $resourceClass, $operation, $context);
    }
}
