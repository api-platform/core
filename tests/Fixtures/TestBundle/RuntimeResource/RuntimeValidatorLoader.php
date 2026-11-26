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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;

final class RuntimeValidatorLoader implements LoaderInterface
{
    public function __construct(private readonly RuntimeResources $resources)
    {
    }

    public function loadClassMetadata(ClassMetadata $metadata): bool
    {
        if (null === $definition = $this->resources->get($metadata->getClassName())) {
            return false;
        }

        $constraints = array_map($this->constraints(...), $definition->attributes);
        $metadata->addConstraint(new Callback(callback: [self::class, 'validate'], payload: $constraints));

        return true;
    }

    /**
     * @param array<string, list<Constraint>> $constraints
     */
    public static function validate(RuntimeObject $object, ExecutionContextInterface $context, array $constraints): void
    {
        foreach ($constraints as $property => $propertyConstraints) {
            $context->getValidator()->inContext($context)->atPath($property)->validate($object->{$property}, $propertyConstraints);
        }
    }

    /**
     * @return list<Constraint>
     */
    private function constraints(Attribute $attribute): array
    {
        $options = $attribute->options;
        $constraints = match ($attribute->type) {
            AttributeType::String, AttributeType::Text => [new Type('string'), ...(isset($options['maxLength']) ? [new Length(max: $options['maxLength'])] : [])],
            AttributeType::Integer, AttributeType::Float => [new Type('numeric'), ...(isset($options['min']) || isset($options['max']) ? [new Range(min: $options['min'] ?? null, max: $options['max'] ?? null)] : [])],
            AttributeType::Boolean => [new Type('bool')],
            AttributeType::Datetime => [new Type(\DateTimeInterface::class)],
            AttributeType::Enum => [new Choice(choices: $options['values'] ?? [])],
            AttributeType::Reference => [new Type((string) $this->resources->classOf((string) $attribute->target()))],
        };

        if ($attribute->collection) {
            $constraints = [new All($constraints)];
        }

        if ($attribute->required) {
            array_unshift($constraints, AttributeType::Boolean === $attribute->type ? new NotNull() : new NotBlank());
        }

        return $constraints;
    }
}
