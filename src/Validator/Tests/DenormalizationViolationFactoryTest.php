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

namespace ApiPlatform\Validator\Tests;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\DenormalizationViolationFactory;
use ApiPlatform\Validator\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;

final class DenormalizationViolationFactoryTest extends TestCase
{
    private DenormalizationViolationFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DenormalizationViolationFactory(
            new LazyLoadingMetadataFactory(new AttributeLoader()),
        );
    }

    public function testNullCurrentTypeWithNotBlankThrowsValidationException(): void
    {
        $exception = NotNormalizableValueException::createForUnexpectedDataType('Type error.', null, ['string'], 'name');

        try {
            $this->factory->handle($exception, $this->operation());
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $violation = $e->getConstraintViolationList()[0];
            $this->assertSame((string) NotBlank::IS_BLANK_ERROR, $violation->getCode());
            $this->assertSame('name', $violation->getPropertyPath());
        }
    }

    public function testNullCurrentTypeWithNotNullThrowsValidationException(): void
    {
        $exception = NotNormalizableValueException::createForUnexpectedDataType('Type error.', null, ['string'], 'description');

        try {
            $this->factory->handle($exception, $this->operation());
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame((string) NotNull::IS_NULL_ERROR, $e->getConstraintViolationList()[0]->getCode());
        }
    }

    public function testWrongTypeWithTypeConstraintThrowsValidationException(): void
    {
        $exception = NotNormalizableValueException::createForUnexpectedDataType('Type error.', 'abc', ['float'], 'score');

        try {
            $this->factory->handle($exception, $this->operation());
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame((string) Type::INVALID_TYPE_ERROR, $e->getConstraintViolationList()[0]->getCode());
        }
    }

    public function testWrongTypeWithOtherConstraintThrowsGenericTypeViolation(): void
    {
        $exception = NotNormalizableValueException::createForUnexpectedDataType('Type error.', 123, ['string'], 'choice');

        try {
            $this->factory->handle($exception, $this->operation());
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame((string) Type::INVALID_TYPE_ERROR, $e->getConstraintViolationList()[0]->getCode());
        }
    }

    public function testWrongTypeWithoutConstraintReturnsVoid(): void
    {
        $exception = NotNormalizableValueException::createForUnexpectedDataType('Type error.', 'abc', ['float'], 'rawFloat');

        // Returns without throwing → caller rethrows for 400.
        $this->factory->handle($exception, $this->operation());
        $this->expectNotToPerformAssertions();
    }

    public function testUnknownClassReturnsVoid(): void
    {
        $exception = NotNormalizableValueException::createForUnexpectedDataType('Type error.', null, ['string'], 'name');

        $this->factory->handle($exception, $this->operation('NotAClass'));
        $this->expectNotToPerformAssertions();
    }

    public function testUnknownPropertyReturnsVoid(): void
    {
        $exception = NotNormalizableValueException::createForUnexpectedDataType('Type error.', null, ['string'], 'missingProperty');

        $this->factory->handle($exception, $this->operation());
        $this->expectNotToPerformAssertions();
    }

    public function testNestedPathReturnsVoid(): void
    {
        $exception = NotNormalizableValueException::createForUnexpectedDataType('Type error.', null, ['string'], 'address.street');

        $this->factory->handle($exception, $this->operation());
        $this->expectNotToPerformAssertions();
    }

    public function testGroupFilteringExcludesConstraintsOutsideActiveGroups(): void
    {
        $exception = NotNormalizableValueException::createForUnexpectedDataType('Type error.', null, ['string'], 'adminOnly');

        // Default group → constraint scoped to "admin" excluded → returns void.
        $this->factory->handle($exception, $this->operation());

        // Active "admin" group → matches.
        try {
            $this->factory->handle($exception, $this->operation(DenormHandlerFixture::class, ['admin']));
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame((string) NotBlank::IS_BLANK_ERROR, $e->getConstraintViolationList()[0]->getCode());
        }
    }

    public function testHandlePartialAggregatesAllErrors(): void
    {
        $errors = [
            NotNormalizableValueException::createForUnexpectedDataType('msg', null, ['string'], 'name'),
            NotNormalizableValueException::createForUnexpectedDataType('msg', 'abc', ['float'], 'rawFloat'),
        ];
        $partial = new PartialDenormalizationException(null, $errors);

        try {
            $this->factory->handle($partial, $this->operation());
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertCount(2, $e->getConstraintViolationList());
            $codes = [];
            foreach ($e->getConstraintViolationList() as $violation) {
                $codes[$violation->getPropertyPath()] = $violation->getCode();
            }
            $this->assertSame((string) NotBlank::IS_BLANK_ERROR, $codes['name']);
            // Unconstrained → generic Type fallback @ INVALID_TYPE_ERROR
            $this->assertSame((string) Type::INVALID_TYPE_ERROR, $codes['rawFloat']);
        }
    }

    /**
     * @param array<string>|null $groups
     */
    private function operation(string $class = DenormHandlerFixture::class, ?array $groups = null): Post
    {
        $operation = new Post(class: $class);
        if (null !== $groups) {
            $operation = $operation->withValidationContext(['groups' => $groups]);
        }

        return $operation;
    }
}

class DenormHandlerFixture
{
    #[NotBlank]
    public string $name = '';

    #[NotNull]
    public string $description = '';

    #[Type('numeric')]
    public float $score = 0.0;

    #[Assert\Choice(choices: ['a', 'b'])]
    public string $choice = 'a';

    public float $rawFloat = 0.0;

    #[NotBlank(groups: ['admin'])]
    public string $adminOnly = '';
}
