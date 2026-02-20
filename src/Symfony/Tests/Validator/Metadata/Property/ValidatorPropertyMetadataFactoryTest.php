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

namespace ApiPlatform\Symfony\Tests\Validator\Metadata\Property;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Symfony\Tests\Fixtures\DummyAtLeastOneOfValidatedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyCollectionValidatedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyCompoundValidatedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyCountValidatedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyIriWithValidationEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyNumericValidatedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyRangeValidatedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummySequentiallyValidatedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyUniqueValidatedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyValidatedChoiceEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyValidatedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyValidatedEntityWithGroupSequence;
use ApiPlatform\Symfony\Tests\Fixtures\DummyValidatedHostnameEntity;
use ApiPlatform\Symfony\Tests\Fixtures\DummyValidatedUlidEntity;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaChoiceRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCollectionRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCountRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanOrEqualRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLessThanOrEqualRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLessThanRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaOneOfRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRangeRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaUniqueRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\ValidatorPropertyMetadataFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Constraints\Ulid;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ValidatorPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    private ClassMetadata $validatorClassMetadata;

    protected function setUp(): void
    {
        $this->validatorClassMetadata = new ClassMetadata(DummyValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($this->validatorClassMetadata);
    }

    public function testCreateWithPropertyWithRequiredConstraints(): void
    {
        $dummyPropertyMetadata = (new ApiProperty())->withDescription('A dummy')->withReadable(true)->withWritable(true);
        $emailPropertyMetadata = (new ApiProperty())->withTypes(['https://schema.org/email'])->withReadable(true)->withWritable(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy', [])->willReturn($dummyPropertyMetadata)->shouldBeCalled();
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyEmail', [])->willReturn($emailPropertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );

        $this->assertEquals(
            $dummyPropertyMetadata->withRequired(true),
            $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy'),
        );

        $this->assertEquals(
            $emailPropertyMetadata->withRequired(false),
            $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyEmail'),
        );
    }

    public function testCreateWithPropertyWithNotRequiredConstraints(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy')->withReadable(true)->withWritable(true);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);
        $expectedPropertyMetadata = $expectedPropertyMetadata->withTypes(['https://schema.org/Date']);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate');

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithoutConstraints(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy')->withReadable(true)->withWritable(true)->withIdentifier(true);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyId', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyId');

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithRequiredConstraintsAndGroupSequence(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withWritable(true);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntityWithGroupSequence::class, 'dummyGroup', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorClassMetadata = new ClassMetadata(DummyValidatedEntityWithGroupSequence::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntityWithGroupSequence::class)->willReturn($validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntityWithGroupSequence::class, 'dummyGroup');

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithRightValidationGroupsAndRequiredConstraints(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withWritable(true);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['dummy']])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['dummy']]);

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithBadValidationGroupsAndRequiredConstraints(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withWritable(true);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['ymmud']])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['ymmud']]);

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithNonStringValidationGroupsAndRequiredConstraints(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withWritable(true);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => [1312]])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => [1312]]);

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithRequiredByDecorated(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withRequired(true)->withTypes(['foo:bar']);
        $expectedPropertyMetadata = (clone $propertyMetadata)->withTypes(['foo:bar', 'https://schema.org/Date']);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate');

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithValidationConstraints(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyIriWithValidationEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $types = [
            'dummyUrl' => 'https://schema.org/url',
            'dummyEmail' => 'https://schema.org/email',
            'dummyUuid' => 'https://schema.org/identifier',
            'dummyCardScheme' => 'https://schema.org/identifier',
            'dummyBic' => 'https://schema.org/identifier',
            'dummyIban' => 'https://schema.org/identifier',
            'dummyDate' => 'https://schema.org/Date',
            'dummyDateTime' => 'https://schema.org/DateTime',
            'dummyTime' => 'https://schema.org/Time',
            'dummyImage' => 'https://schema.org/image',
            'dummyFile' => 'https://schema.org/MediaObject',
            'dummyCurrency' => 'https://schema.org/priceCurrency',
            'dummyIsbn' => 'https://schema.org/isbn',
            'dummyIssn' => 'https://schema.org/issn',
        ];

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        foreach ($types as $property => $iri) {
            $decoratedPropertyMetadataFactory->create(DummyIriWithValidationEntity::class, $property, [])->willReturn(new ApiProperty())->shouldBeCalled();
        }

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyIriWithValidationEntity::class)->willReturn($validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );

        foreach ($types as $property => $iri) {
            $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyIriWithValidationEntity::class, $property);
            $this->assertEquals($iri, $resultedPropertyMetadata->getTypes()[0]);
        }
    }

    public function testCreateWithPropertyLengthRestriction(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $property = 'dummy';
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, $property, [])->willReturn(
            (new ApiProperty())->withNativeType(Type::string())
        )->shouldBeCalled();

        $lengthRestrictions = new PropertySchemaLengthRestriction();
        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [$lengthRestrictions]
        );

        $schema = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, $property)->getSchema();
        $this->assertNotNull($schema);
        $this->assertArrayHasKey('minLength', $schema);
        $this->assertArrayHasKey('maxLength', $schema);
    }

    public function testCreateWithPropertyRegexRestriction(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy', [])->willReturn(
            new ApiProperty()
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaRegexRestriction()]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy')->getSchema();
        $this->assertNotNull($schema);
        $this->assertArrayHasKey('pattern', $schema);
        $this->assertEquals('^(dummy)$', $schema['pattern']);
    }

    #[DataProvider('providePropertySchemaFormatCases')]
    public function testCreateWithPropertyFormatRestriction(string $property, string $class, array $expectedSchema): void
    {
        $validatorClassMetadata = new ClassMetadata($class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor($class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create($class, $property, [])->willReturn(
            new ApiProperty()
        )->shouldBeCalled();
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaFormat()]
        );
        $schema = $validationPropertyMetadataFactory->create($class, $property)->getSchema();

        $this->assertEquals($expectedSchema, $schema);
    }

    public static function providePropertySchemaFormatCases(): \Generator
    {
        yield ['dummyEmail', DummyValidatedEntity::class, ['format' => 'email']];
        yield ['dummyUuid', DummyValidatedEntity::class, ['format' => 'uuid']];
        yield ['dummyIpv4', DummyValidatedEntity::class, ['format' => 'ipv4']];
        yield ['dummyIpv6', DummyValidatedEntity::class, ['format' => 'ipv6']];
        yield ['dummyUrl', DummyValidatedEntity::class, ['format' => 'uri']];
        if (class_exists(Ulid::class)) {
            yield ['dummyUlid', DummyValidatedUlidEntity::class, ['format' => 'ulid']];
        }
        if (class_exists(Hostname::class)) {
            yield ['dummyHostname', DummyValidatedHostnameEntity::class, ['format' => 'hostname']];
        }
    }

    public function testCreateWithSequentiallyConstraint(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummySequentiallyValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummySequentiallyValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummySequentiallyValidatedEntity::class, 'dummy', [])->willReturn(
            (new ApiProperty())->withNativeType(Type::string())
        )->shouldBeCalled();
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaLengthRestriction(), new PropertySchemaRegexRestriction()]
        );
        $schema = $validationPropertyMetadataFactory->create(DummySequentiallyValidatedEntity::class, 'dummy')->getSchema();

        $this->assertNotNull($schema);
        $this->assertArrayHasKey('minLength', $schema);
        $this->assertArrayHasKey('maxLength', $schema);
        $this->assertArrayHasKey('pattern', $schema);
    }

    public function testCreateWithCompoundConstraint(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyCompoundValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyCompoundValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyCompoundValidatedEntity::class, 'dummy', [])->willReturn(
            (new ApiProperty())->withNativeType(Type::string())
        )->shouldBeCalled();
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaLengthRestriction(), new PropertySchemaRegexRestriction()]
        );
        $schema = $validationPropertyMetadataFactory->create(DummyCompoundValidatedEntity::class, 'dummy')->getSchema();

        $this->assertNotNull($schema);
        $this->assertArrayHasKey('minLength', $schema);
        $this->assertArrayHasKey('maxLength', $schema);
        $this->assertArrayHasKey('pattern', $schema);
    }

    public function testCreateWithAtLeastOneOfConstraint(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyAtLeastOneOfValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyAtLeastOneOfValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyAtLeastOneOfValidatedEntity::class, 'dummy', [])->willReturn(
            (new ApiProperty())->withNativeType(Type::string())
        )->shouldBeCalled();
        $restrictionsMetadata = [new PropertySchemaLengthRestriction(), new PropertySchemaRegexRestriction()];
        $restrictionsMetadata = [new PropertySchemaOneOfRestriction($restrictionsMetadata), new PropertySchemaLengthRestriction(), new PropertySchemaRegexRestriction()];
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            $restrictionsMetadata
        );
        $schema = $validationPropertyMetadataFactory->create(DummyAtLeastOneOfValidatedEntity::class, 'dummy')->getSchema();

        $this->assertNotNull($schema);
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertEquals([
            ['pattern' => '^(.*#.*)$'],
            ['minLength' => 10],
        ], $schema['oneOf']);
    }

    public function testCreateWithPropertyUniqueRestriction(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyUniqueValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyUniqueValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyUniqueValidatedEntity::class, 'dummyItems', [])->willReturn(
            new ApiProperty()
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaUniqueRestriction()]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyUniqueValidatedEntity::class, 'dummyItems')->getSchema();

        $this->assertEquals(['uniqueItems' => true], $schema);
    }

    #[IgnoreDeprecations]
    public function testLegacyCreateWithRangeConstraint(): void
    {
        if (!class_exists(LegacyType::class)) {
            $this->markTestSkipped('symfony/property-info is not installed.');
        }

        $cases = [
            'min int' => ['type' => new LegacyType(LegacyType::BUILTIN_TYPE_INT), 'property' => 'dummyIntMin', 'expectedSchema' => ['minimum' => 1]],
            'max int' => ['type' => new LegacyType(LegacyType::BUILTIN_TYPE_INT), 'property' => 'dummyIntMax', 'expectedSchema' => ['maximum' => 10]],
            'min/max int' => ['type' => new LegacyType(LegacyType::BUILTIN_TYPE_INT), 'property' => 'dummyIntMinMax', 'expectedSchema' => ['minimum' => 1, 'maximum' => 10]],
            'min float' => ['type' => new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT), 'property' => 'dummyFloatMin', 'expectedSchema' => ['minimum' => 1.5]],
            'max float' => ['type' => new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT), 'property' => 'dummyFloatMax', 'expectedSchema' => ['maximum' => 10.5]],
            'min/max float' => ['type' => new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT), 'property' => 'dummyFloatMinMax', 'expectedSchema' => ['minimum' => 1.5, 'maximum' => 10.5]],
        ];

        foreach ($cases as ['type' => $type, 'property' => $property, 'expectedSchema' => $expectedSchema]) {
            $validatorClassMetadata = new ClassMetadata(DummyRangeValidatedEntity::class);
            (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

            $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
            $validatorMetadataFactory->getMetadataFor(DummyRangeValidatedEntity::class)
                ->willReturn($validatorClassMetadata)
                ->shouldBeCalled();

            $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
            $decoratedPropertyMetadataFactory->create(DummyRangeValidatedEntity::class, $property, [])->willReturn(
                (new ApiProperty())->withBuiltinTypes([$type])
            )->shouldBeCalled();
            $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
                $validatorMetadataFactory->reveal(),
                $decoratedPropertyMetadataFactory->reveal(),
                [new PropertySchemaRangeRestriction()]
            );
            $schema = $validationPropertyMetadataFactory->create(DummyRangeValidatedEntity::class, $property)->getSchema();

            $this->assertEquals($expectedSchema, $schema);
        }
    }

    #[DataProvider('provideRangeConstraintCasesWithNativeType')]
    public function testCreateWithRangeConstraintWithNativeType(Type $type, string $property, array $expectedSchema): void // Use new Type
    {
        $validatorClassMetadata = new ClassMetadata(DummyRangeValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyRangeValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyRangeValidatedEntity::class, $property, [])->willReturn(
            (new ApiProperty())->withNativeType($type) // Use withNativeType and new Type
        )->shouldBeCalled();
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaRangeRestriction()]
        );
        $schema = $validationPropertyMetadataFactory->create(DummyRangeValidatedEntity::class, $property)->getSchema();

        $this->assertEquals($expectedSchema, $schema);
    }

    public static function provideRangeConstraintCasesWithNativeType(): \Generator
    {
        yield 'native type: min int' => ['type' => Type::int(), 'property' => 'dummyIntMin', 'expectedSchema' => ['minimum' => 1]];
        yield 'native type: max int' => ['type' => Type::int(), 'property' => 'dummyIntMax', 'expectedSchema' => ['maximum' => 10]];
        yield 'native type: min/max int' => ['type' => Type::int(), 'property' => 'dummyIntMinMax', 'expectedSchema' => ['minimum' => 1, 'maximum' => 10]];
        yield 'native type: min float' => ['type' => Type::float(), 'property' => 'dummyFloatMin', 'expectedSchema' => ['minimum' => 1.5]];
        yield 'native type: max float' => ['type' => Type::float(), 'property' => 'dummyFloatMax', 'expectedSchema' => ['maximum' => 10.5]];
        yield 'native type: min/max float' => ['type' => Type::float(), 'property' => 'dummyFloatMinMax', 'expectedSchema' => ['minimum' => 1.5, 'maximum' => 10.5]];
    }

    #[IgnoreDeprecations]
    public function testCreateWithPropertyChoiceRestriction(): void
    {
        if (!class_exists(LegacyType::class)) {
            $this->markTestSkipped('symfony/property-info is not installed.');
        }

        $cases = [
            'single choice' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]), 'property' => 'dummySingleChoice', 'expectedSchema' => ['enum' => ['a', 'b']]],
            'single choice callback' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]), 'property' => 'dummySingleChoiceCallback', 'expectedSchema' => ['enum' => ['a', 'b', 'c', 'd']]],
            'multi choice' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoice', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b']]]],
            'multi choice callback' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoiceCallback', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']]]],
            'multi choice min' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoiceMin', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'minItems' => 2]],
            'multi choice max' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoiceMax', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'maxItems' => 4]],
            'multi choice min/max' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoiceMinMax', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'minItems' => 2, 'maxItems' => 4]],
        ];

        foreach ($cases as ['propertyMetadata' => $propertyMetadata, 'property' => $property, 'expectedSchema' => $expectedSchema]) {
            $validatorClassMetadata = new ClassMetadata(DummyValidatedChoiceEntity::class);
            (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

            $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
            $validatorMetadataFactory->getMetadataFor(DummyValidatedChoiceEntity::class)
                ->willReturn($validatorClassMetadata)
                ->shouldBeCalled();

            $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
            $decoratedPropertyMetadataFactory->create(DummyValidatedChoiceEntity::class, $property, [])->willReturn(
                $propertyMetadata
            )->shouldBeCalled();

            $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
                $validatorMetadataFactory->reveal(),
                $decoratedPropertyMetadataFactory->reveal(),
                [new PropertySchemaChoiceRestriction()]
            );

            $schema = $validationPropertyMetadataFactory->create(DummyValidatedChoiceEntity::class, $property)->getSchema();

            $this->assertEquals($expectedSchema, $schema);
        }
    }

    #[DataProvider('provideChoiceConstraintCasesWithNativeType')]
    public function testCreateWithPropertyChoiceRestrictionWithNativeType(ApiProperty $propertyMetadata, string $property, array $expectedSchema): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyValidatedChoiceEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedChoiceEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedChoiceEntity::class, $property, [])->willReturn(
            $propertyMetadata // Provider now sends ApiProperty configured with withNativeType
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaChoiceRestriction()]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyValidatedChoiceEntity::class, $property)->getSchema();

        $this->assertEquals($expectedSchema, $schema);
    }

    public static function provideChoiceConstraintCasesWithNativeType(): \Generator
    {
        yield 'native type: single choice' => ['propertyMetadata' => (new ApiProperty())->withNativeType(Type::string()), 'property' => 'dummySingleChoice', 'expectedSchema' => ['enum' => ['a', 'b']]];
        yield 'native type: single choice callback' => ['propertyMetadata' => (new ApiProperty())->withNativeType(Type::string()), 'property' => 'dummySingleChoiceCallback', 'expectedSchema' => ['enum' => ['a', 'b', 'c', 'd']]];
        yield 'native type: multi choice' => ['propertyMetadata' => (new ApiProperty())->withNativeType(Type::string()), 'property' => 'dummyMultiChoice', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b']]]];
        yield 'native type: multi choice callback' => ['propertyMetadata' => (new ApiProperty())->withNativeType(Type::string()), 'property' => 'dummyMultiChoiceCallback', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']]]];
        yield 'native type: multi choice min' => ['propertyMetadata' => (new ApiProperty())->withNativeType(Type::string()), 'property' => 'dummyMultiChoiceMin', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'minItems' => 2]];
        yield 'native type: multi choice max' => ['propertyMetadata' => (new ApiProperty())->withNativeType(Type::string()), 'property' => 'dummyMultiChoiceMax', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'maxItems' => 4]];
        yield 'native type: multi choice min/max' => ['propertyMetadata' => (new ApiProperty())->withNativeType(Type::string()), 'property' => 'dummyMultiChoiceMinMax', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'minItems' => 2, 'maxItems' => 4]];
    }

    #[DataProvider('provideCountConstraintCases')]
    public function testCreateWithPropertyCountRestriction(string $property, array $expectedSchema): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyCountValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyCountValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyCountValidatedEntity::class, $property, [])->willReturn(
            new ApiProperty()
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaCountRestriction()]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyCountValidatedEntity::class, $property)->getSchema();

        $this->assertEquals($expectedSchema, $schema);
    }

    public static function provideCountConstraintCases(): \Generator
    {
        yield 'min' => ['property' => 'dummyMin', 'expectedSchema' => ['minItems' => 1]];
        yield 'max' => ['property' => 'dummyMax', 'expectedSchema' => ['maxItems' => 10]];
        yield 'min/max' => ['property' => 'dummyMinMax', 'expectedSchema' => ['minItems' => 1, 'maxItems' => 10]];
    }

    public function testCreateWithPropertyCollectionRestriction(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyCollectionValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyCollectionValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyCollectionValidatedEntity::class, 'dummyData', [])->willReturn(
            (new ApiProperty())->withNativeType(Type::list())
        )->shouldBeCalled();

        $greaterThanRestriction = new PropertySchemaGreaterThanRestriction();
        $lengthRestriction = new PropertySchemaLengthRestriction();
        $regexRestriction = new PropertySchemaRegexRestriction();
        $formatRestriction = new PropertySchemaFormat();
        $restrictionsMetadata = [
            $greaterThanRestriction,
            $lengthRestriction,
            $regexRestriction,
            $formatRestriction,
            new PropertySchemaCollectionRestriction([
                $greaterThanRestriction,
                $lengthRestriction,
                $regexRestriction,
                $formatRestriction,
                new PropertySchemaCollectionRestriction([
                    $greaterThanRestriction,
                    $lengthRestriction,
                    $regexRestriction,
                    $formatRestriction,
                ]),
            ]),
        ];

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            $restrictionsMetadata
        );

        $schema = $validationPropertyMetadataFactory->create(DummyCollectionValidatedEntity::class, 'dummyData')->getSchema();

        $this->assertEquals([
            'type' => 'object',
            'properties' => new \ArrayObject([
                'name' => new \ArrayObject(),
                'email' => ['format' => 'email', 'minLength' => 2, 'maxLength' => 255],
                'phone' => ['pattern' => '^([+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*)$'],
                'age' => [
                    'exclusiveMinimum' => 0,
                    'minimum' => 0,
                ],
                'social' => [
                    'type' => 'object',
                    'properties' => new \ArrayObject([
                        'githubUsername' => new \ArrayObject(),
                    ]),
                    'additionalProperties' => false,
                    'required' => ['githubUsername'],
                ],
            ]),
            'additionalProperties' => true,
            'required' => ['name', 'email', 'social'],
        ], $schema);
    }

    #[IgnoreDeprecations]
    public function testCreateWithPropertyNumericRestriction(): void
    {
        if (!class_exists(LegacyType::class)) {
            $this->markTestSkipped('symfony/property-info is not installed.');
        }

        $cases = [
            [
                'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]),
                'property' => 'greaterThanMe',
                'expectedSchema' => ['exclusiveMinimum' => 10, 'minimum' => 10],
            ],
            [
                'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]),
                'property' => 'greaterThanOrEqualToMe',
                'expectedSchema' => ['minimum' => 10.99],
            ],
            [
                'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]),
                'property' => 'lessThanMe',
                'expectedSchema' => ['exclusiveMaximum' => 99, 'maximum' => 99],
            ],
            [
                'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]),
                'property' => 'lessThanOrEqualToMe',
                'expectedSchema' => ['maximum' => 99.33],
            ],
            [
                'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]),
                'property' => 'positive',
                'expectedSchema' => ['exclusiveMinimum' => 0, 'minimum' => 0],
            ],
            [
                'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]),
                'property' => 'positiveOrZero',
                'expectedSchema' => ['minimum' => 0],
            ],
            [
                'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]),
                'property' => 'negative',
                'expectedSchema' => ['exclusiveMaximum' => 0, 'maximum' => 0],
            ],
            [
                'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]),
                'property' => 'negativeOrZero',
                'expectedSchema' => ['maximum' => 0],
            ],
        ];

        foreach ($cases as ['propertyMetadata' => $propertyMetadata, 'property' => $property, 'expectedSchema' => $expectedSchema]) {
            $validatorClassMetadata = new ClassMetadata(DummyNumericValidatedEntity::class);
            (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

            $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
            $validatorMetadataFactory->getMetadataFor(DummyNumericValidatedEntity::class)
                ->willReturn($validatorClassMetadata)
                ->shouldBeCalled();

            $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
            $decoratedPropertyMetadataFactory->create(DummyNumericValidatedEntity::class, $property, [])->willReturn(
                $propertyMetadata
            )->shouldBeCalled();

            $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
                $validatorMetadataFactory->reveal(),
                $decoratedPropertyMetadataFactory->reveal(),
                [
                    new PropertySchemaGreaterThanOrEqualRestriction(),
                    new PropertySchemaGreaterThanRestriction(),
                    new PropertySchemaLessThanOrEqualRestriction(),
                    new PropertySchemaLessThanRestriction(),
                ]
            );

            $schema = $validationPropertyMetadataFactory->create(DummyNumericValidatedEntity::class, $property)->getSchema();

            $this->assertEquals($expectedSchema, $schema);
        }
    }

    #[DataProvider('provideNumericConstraintCasesWithNativeType')]
    public function testCreateWithPropertyNumericRestrictionWithNativeType(ApiProperty $propertyMetadata, string $property, array $expectedSchema): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyNumericValidatedEntity::class);
        (new AttributeLoader())->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyNumericValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyNumericValidatedEntity::class, $property, [])->willReturn(
            $propertyMetadata
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [
                new PropertySchemaGreaterThanOrEqualRestriction(),
                new PropertySchemaGreaterThanRestriction(),
                new PropertySchemaLessThanOrEqualRestriction(),
                new PropertySchemaLessThanRestriction(),
            ]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyNumericValidatedEntity::class, $property)->getSchema();

        $this->assertEquals($expectedSchema, $schema);
    }

    public static function provideNumericConstraintCasesWithNativeType(): \Generator
    {
        yield [
            'propertyMetadata' => (new ApiProperty())->withNativeType(Type::int()),
            'property' => 'greaterThanMe',
            'expectedSchema' => ['exclusiveMinimum' => 10, 'minimum' => 10],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withNativeType(Type::float()),
            'property' => 'greaterThanOrEqualToMe',
            'expectedSchema' => ['minimum' => 10.99],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withNativeType(Type::int()),
            'property' => 'lessThanMe',
            'expectedSchema' => ['exclusiveMaximum' => 99, 'maximum' => 99],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withNativeType(Type::float()),
            'property' => 'lessThanOrEqualToMe',
            'expectedSchema' => ['maximum' => 99.33],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withNativeType(Type::int()),
            'property' => 'positive',
            'expectedSchema' => ['exclusiveMinimum' => 0, 'minimum' => 0],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withNativeType(Type::int()),
            'property' => 'positiveOrZero',
            'expectedSchema' => ['minimum' => 0],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withNativeType(Type::int()),
            'property' => 'negative',
            'expectedSchema' => ['exclusiveMaximum' => 0, 'maximum' => 0],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withNativeType(Type::int()),
            'property' => 'negativeOrZero',
            'expectedSchema' => ['maximum' => 0],
        ];
    }

    public function testCallableGroup(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withWritable(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => [DummyValidatedEntity::class, 'getValidationGroups']])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => [DummyValidatedEntity::class, 'getValidationGroups']]);
    }
}
