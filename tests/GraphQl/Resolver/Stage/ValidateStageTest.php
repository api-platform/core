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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver\Stage;

use ApiPlatform\Core\GraphQl\Resolver\Stage\ValidateStage;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Core\Validator\Exception\ValidationException;
use ApiPlatform\Core\Validator\ValidatorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ValidateStageTest extends TestCase
{
    use ProphecyTrait;

    /** @var ValidateStage */
    private $validateStage;
    private $resourceMetadataFactoryProphecy;
    private $validatorProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->validatorProphecy = $this->prophesize(ValidatorInterface::class);

        $this->validateStage = new ValidateStage(
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->validatorProphecy->reveal()
        );
    }

    public function testApplyDisabled(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['validate' => false],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->validatorProphecy->validate(Argument::cetera())->shouldNotBeCalled();

        ($this->validateStage)(new \stdClass(), $resourceClass, $operationName, []);
    }

    public function testApply(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $validationGroups = ['group'];
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['validation_groups' => $validationGroups],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $object = new \stdClass();
        $this->validatorProphecy->validate($object, ['groups' => $validationGroups])->shouldBeCalled();

        ($this->validateStage)($object, $resourceClass, $operationName, []);
    }

    public function testApplyNotValidated(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $validationGroups = ['group'];
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['validation_groups' => $validationGroups],
        ]);
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $context = ['info' => $info];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $object = new \stdClass();
        $this->validatorProphecy->validate($object, ['groups' => $validationGroups])->shouldBeCalled()->willThrow(new ValidationException());

        $this->expectException(ValidationException::class);

        ($this->validateStage)($object, $resourceClass, $operationName, $context);
    }
}
