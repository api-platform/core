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
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Core\Validator\Exception\ValidationException;
use ApiPlatform\Core\Validator\ValidatorInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
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
    private $resourceMetadataCollectionFactoryProphecy;
    private $validatorProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->validatorProphecy = $this->prophesize(ValidatorInterface::class);

        $this->validateStage = new ValidateStage(
            $this->resourceMetadataCollectionFactoryProphecy->reveal(),
            $this->validatorProphecy->reveal()
        );
    }

    public function testApplyDisabled(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = (new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => (new Query())->withValidate(false)])]));
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->validatorProphecy->validate(Argument::cetera())->shouldNotBeCalled();

        ($this->validateStage)(new \stdClass(), $resourceClass, $operationName, []);
    }

    public function testApply(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $validationGroups = ['group'];
        $resourceMetadata = (new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => (new Query())->withValidationContext(['groups' => $validationGroups])])]));
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $object = new \stdClass();
        $this->validatorProphecy->validate($object, ['groups' => $validationGroups])->shouldBeCalled();

        ($this->validateStage)($object, $resourceClass, $operationName, []);
    }

    public function testApplyNotValidated(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $validationGroups = ['group'];
        $resourceMetadata = (new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => (new Query())->withValidationContext(['groups' => $validationGroups])])]));
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $context = ['info' => $info];
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $object = new \stdClass();
        $this->validatorProphecy->validate($object, ['groups' => $validationGroups])->shouldBeCalled()->willThrow(new ValidationException());

        $this->expectException(ValidationException::class);

        ($this->validateStage)($object, $resourceClass, $operationName, $context);
    }
}
